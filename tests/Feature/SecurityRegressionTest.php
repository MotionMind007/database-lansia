<?php

namespace Tests\Feature;

use App\Http\Controllers\App\ExportController;
use App\Http\Middleware\CheckRole;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Support\SecureUploadStorage;
use App\Support\SurveyResponseAccess;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    public function test_survey_response_visibility_matches_roles(): void
    {
        $draft = new SurveyResponse([
            'surveyor_id' => 10,
            'status' => SurveyResponse::STATUS_DRAFT,
        ]);

        $submitted = new SurveyResponse([
            'surveyor_id' => 10,
            'status' => SurveyResponse::STATUS_SUBMITTED,
        ]);

        $this->assertTrue(SurveyResponseAccess::canView($this->userWithRole('administrator'), $draft));
        $this->assertTrue(SurveyResponseAccess::canView($this->userWithRole('super admin'), $draft));
        $this->assertTrue(SurveyResponseAccess::canView($this->userWithRole('surveyor', 10), $draft));
        $this->assertFalse(SurveyResponseAccess::canView($this->userWithRole('surveyor', 99), $draft));
        $this->assertFalse(SurveyResponseAccess::canView($this->userWithRole('verifikator'), $draft));
        $this->assertTrue(SurveyResponseAccess::canView($this->userWithRole('verifikator'), $submitted));
        $this->assertFalse(SurveyResponseAccess::canView($this->userWithRole(null), $submitted));
    }

    public function test_export_route_requires_administrator_or_surveyor_role(): void
    {
        $route = Route::getRoutes()->getByName('app.export');

        $this->assertNotNull($route);

        $middleware = $route->gatherMiddleware();

        $this->assertTrue(collect($middleware)->contains(
            fn (string $entry) => str_contains($entry, CheckRole::class)
                && str_contains($entry, 'administrator')
                && str_contains($entry, 'surveyor')
        ));
    }

    public function test_check_role_accepts_any_assigned_role(): void
    {
        $request = Request::create('/app/export', 'GET');
        $request->setUserResolver(fn (): User => $this->userWithRoles(['administrator', 'surveyor']));

        $response = (new CheckRole())->handle(
            $request,
            fn () => response('ok'),
            'surveyor'
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_survey_response_audit_fields_are_not_mass_assignable(): void
    {
        $response = new SurveyResponse();

        $response->fill([
            'status' => SurveyResponse::STATUS_VERIFIED,
            'verified_by' => 99,
            'created_by' => 99,
            'updated_by' => 99,
        ]);

        $this->assertSame(SurveyResponse::STATUS_VERIFIED, $response->status);
        $this->assertNull($response->verified_by);
        $this->assertNull($response->created_by);
        $this->assertNull($response->updated_by);
    }

    public function test_filament_panel_requires_active_administrator(): void
    {
        $panel = Panel::make();

        $this->assertTrue($this->userWithRole('administrator')->canAccessPanel($panel));
        $this->assertTrue($this->userWithRole('super admin')->canAccessPanel($panel));
        $this->assertFalse($this->userWithRole('surveyor')->canAccessPanel($panel));

        $inactiveAdmin = $this->userWithRole('administrator');
        $inactiveAdmin->is_active = false;

        $this->assertFalse($inactiveAdmin->canAccessPanel($panel));
    }

    public function test_login_post_route_is_throttled(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => in_array('POST', $route->methods(), true) && $route->uri() === 'login'
        );

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
    }

    public function test_web_responses_include_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_legacy_root_controllers_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('LansiaController.php'));
        $this->assertFileDoesNotExist(app_path('SurveyController.php'));
    }

    public function test_csv_formula_values_are_neutralized(): void
    {
        $this->assertSame("'=cmd", ExportController::safeCsvValue('=cmd'));
        $this->assertSame("'+SUM(A1:A2)", ExportController::safeCsvValue('+SUM(A1:A2)'));
        $this->assertSame("'-10", ExportController::safeCsvValue('-10'));
        $this->assertSame("'@HYPERLINK", ExportController::safeCsvValue('@HYPERLINK'));
        $this->assertSame('normal text', ExportController::safeCsvValue('normal text'));
    }

    public function test_private_file_routes_require_authentication(): void
    {
        foreach (['app.documents.show', 'app.respondents.photo'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_activity_log_route_requires_administrator_or_super_admin_role(): void
    {
        $route = Route::getRoutes()->getByName('app.activity-logs.index');

        $this->assertNotNull($route);

        $middleware = $route->gatherMiddleware();

        $this->assertTrue(collect($middleware)->contains(
            fn (string $entry) => str_contains($entry, CheckRole::class)
                && str_contains($entry, 'administrator')
                && str_contains($entry, 'super admin')
        ));
    }

    public function test_village_search_route_is_authenticated_and_limited(): void
    {
        $route = Route::getRoutes()->getByName('app.wilayah.villages.search');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function test_user_admin_form_has_optional_edit_password_and_role_picker(): void
    {
        $form = file_get_contents(app_path('Filament/Resources/Users/Schemas/UserForm.php'));

        $this->assertStringContainsString("TextInput::make('password')", $form);
        $this->assertStringContainsString("->required(fn (string \$operation): bool => \$operation === 'create')", $form);
        $this->assertStringContainsString('->dehydrated(fn (?string $state): bool => filled($state))', $form);
        $this->assertStringContainsString("CheckboxList::make('roles')", $form);
    }

    public function test_survey_controller_uses_form_requests_for_store_and_update(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/App/SurveyController.php'));

        $this->assertStringContainsString('public function store(StoreSurveyRequest $request)', $controller);
        $this->assertStringContainsString('public function update(UpdateSurveyRequest $request, $id)', $controller);
        $this->assertStringNotContainsString('$request->validate([', $controller);
    }

    public function test_survey_create_and_edit_views_use_shared_partials(): void
    {
        $create = file_get_contents(resource_path('views/app/survey/create.blade.php'));
        $edit = file_get_contents(resource_path('views/app/survey/edit.blade.php'));

        foreach ([$create, $edit] as $view) {
            $this->assertStringContainsString("@include('app.survey.partials.styles')", $view);
            $this->assertStringContainsString("@include('app.survey.partials.steps')", $view);
            $this->assertStringNotContainsString('@push(\'styles\')', $view);
            $this->assertSame(1, substr_count($view, "document.addEventListener('DOMContentLoaded'"));
        }

        $this->assertFileExists(resource_path('views/app/survey/partials/styles.blade.php'));
        $this->assertFileExists(resource_path('views/app/survey/partials/steps.blade.php'));
    }

    public function test_database_backup_operational_files_are_present(): void
    {
        $console = file_get_contents(base_path('routes/console.php'));
        $databaseQueueEnv = file_get_contents(base_path('deploy/env/production.database-queue.env.example'));
        $redisEnv = file_get_contents(base_path('deploy/env/production.redis.env.example'));

        $this->assertStringContainsString('app:backup-database', $console);
        $this->assertStringContainsString('Symfony\Component\Process\Process', $console);
        $this->assertFileExists(config_path('backup.php'));
        $this->assertFileExists(base_path('docs/backup-restore.md'));
        $this->assertFileExists(base_path('deploy/cron/lansia-papua-database-backup.cron.example'));

        foreach ([$databaseQueueEnv, $redisEnv] as $env) {
            $this->assertStringContainsString('BACKUP_DATABASE_PATH=backups/database', $env);
            $this->assertStringContainsString('BACKUP_DATABASE_KEEP_LATEST=14', $env);
            $this->assertStringContainsString('BACKUP_DATABASE_MAX_AGE_HOURS=26', $env);
        }
    }

    public function test_production_monitoring_operational_files_are_present(): void
    {
        $console = file_get_contents(base_path('routes/console.php'));
        $operations = file_get_contents(base_path('docs/production-operations.md'));

        $this->assertStringContainsString('app:production-status', $console);
        $this->assertStringContainsString('database backup freshness', $console);
        $this->assertFileExists(base_path('docs/incident-runbook.md'));
        $this->assertFileExists(base_path('deploy/cron/lansia-papua-production-status.cron.example'));
        $this->assertStringContainsString('docs/incident-runbook.md', $operations);
        $this->assertStringContainsString('deploy/cron/lansia-papua-production-status.cron.example', $operations);
    }

    public function test_secure_upload_storage_rejects_unsafe_paths(): void
    {
        $storage = new SecureUploadStorage();

        $this->assertTrue($storage->validPrivatePath('documents/1/file.pdf', ['documents']));
        $this->assertFalse($storage->validPrivatePath('../.env', ['documents']));
        $this->assertFalse($storage->validPrivatePath('/documents/1/file.pdf', ['documents']));
        $this->assertFalse($storage->validPrivatePath('photos/1/file.jpg', ['documents']));
    }

    public function test_secure_upload_storage_deletes_only_allowed_paths(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $storage = new SecureUploadStorage();
        Storage::disk('local')->put('photos/1/profile.jpg', 'photo');
        Storage::disk('local')->put('documents/1/ktp.pdf', 'document');

        $this->assertTrue($storage->delete('photos/1/profile.jpg', ['photos']));
        $this->assertFalse($storage->delete('../documents/1/ktp.pdf', ['documents']));

        Storage::disk('local')->assertMissing('photos/1/profile.jpg');
        Storage::disk('local')->assertExists('documents/1/ktp.pdf');
    }

    private function userWithRole(?string $role, int $id = 1): User
    {
        return $this->userWithRoles($role ? [$role] : [], $id);
    }

    private function userWithRoles(array $roles, int $id = 1): User
    {
        $user = new User(['is_active' => true]);
        $user->id = $id;

        $roles = collect($roles)
            ->map(fn (string $role): Role => Role::make(['name' => $role, 'guard_name' => 'web']));

        $user->setRelation('roles', $roles);

        return $user;
    }
}
