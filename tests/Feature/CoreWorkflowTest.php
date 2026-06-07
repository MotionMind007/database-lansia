<?php

namespace Tests\Feature;

use App\Jobs\SyncDashboardFacts;
use App\Models\Region;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_and_failure_are_handled(): void
    {
        $user = $this->userWithRole('surveyor', [
            'email' => 'surveyor@example.test',
            'username' => 'surveyor',
            'password' => 'password-rahasia',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-rahasia',
        ])->assertRedirect(route('app.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_surveyor_can_submit_minimal_valid_survey(): void
    {
        Queue::fake();
        $this->seedSurveyPrerequisites();
        $surveyor = $this->userWithRole('surveyor');
        $village = $this->village();

        $this->actingAs($surveyor)
            ->post(route('app.survey.store'), [
                'questionnaire_number' => 'WF-001',
                'region_id' => $village->id,
                'interview_date' => now()->toDateString(),
                'full_name' => 'Maria Papua',
                'gender' => 'female',
                'age' => 72,
                'action' => 'submit',
                'penghasilan' => 'Rp 1.000.000 - Rp 2.500.000',
                'keluhan_kes' => 'tidak',
            ])
            ->assertRedirect(route('app.lansia.index'));

        $response = SurveyResponse::where('questionnaire_number', 'WF-001')->firstOrFail();

        $this->assertSame(SurveyResponse::STATUS_SUBMITTED, $response->status);
        $this->assertSame($surveyor->id, $response->surveyor_id);
        $this->assertSame($surveyor->id, $response->created_by);
        $this->assertDatabaseHas('respondents', [
            'full_name' => 'Maria Papua',
            'region_id' => $village->id,
        ]);
        $this->assertDatabaseHas('survey_answers', [
            'survey_response_id' => $response->id,
        ]);

        Queue::assertPushed(SyncDashboardFacts::class);
    }

    public function test_surveyor_can_only_see_own_data(): void
    {
        $this->seedSurveyPrerequisites();
        $owner = $this->userWithRole('surveyor', ['name' => 'Surveyor Pemilik']);
        $other = $this->userWithRole('surveyor', ['name' => 'Surveyor Lain']);

        $ownResponse = $this->surveyResponseFor($owner, ['questionnaire_number' => 'OWN-001']);
        $otherResponse = $this->surveyResponseFor($other, ['questionnaire_number' => 'OTHER-001']);

        $this->actingAs($owner)
            ->get(route('app.lansia.show', $ownResponse->id))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('app.lansia.show', $otherResponse->id))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('app.lansia.index'))
            ->assertOk()
            ->assertSee('OWN-001')
            ->assertDontSee('OTHER-001');
    }

    public function test_verifikator_can_review_and_approve_submitted_survey(): void
    {
        Queue::fake();
        $this->seedSurveyPrerequisites();
        $surveyor = $this->userWithRole('surveyor');
        $verifikator = $this->userWithRole('verifikator');
        $response = $this->surveyResponseFor($surveyor, [
            'questionnaire_number' => 'VER-001',
            'status' => SurveyResponse::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($verifikator)
            ->get(route('app.verification.show', $response->id))
            ->assertOk()
            ->assertSee('VER-001');

        $this->actingAs($verifikator)
            ->post(route('app.verification.verify', $response->id), [
                'status' => SurveyResponse::STATUS_VERIFIED,
                'note' => null,
            ])
            ->assertRedirect(route('app.verification.index'));

        $response->refresh();

        $this->assertSame(SurveyResponse::STATUS_VERIFIED, $response->status);
        $this->assertSame($verifikator->id, $response->verified_by);
        $this->assertNotNull($response->verified_at);
        $this->assertDatabaseHas('verification_logs', [
            'survey_response_id' => $response->id,
            'status' => SurveyResponse::STATUS_VERIFIED,
            'verified_by' => $verifikator->id,
        ]);

        Queue::assertPushed(SyncDashboardFacts::class);
    }

    public function test_verifikator_can_request_revision_with_note(): void
    {
        Queue::fake();
        $this->seedSurveyPrerequisites();
        $surveyor = $this->userWithRole('surveyor');
        $verifikator = $this->userWithRole('verifikator');
        $response = $this->surveyResponseFor($surveyor, [
            'questionnaire_number' => 'REV-001',
            'status' => SurveyResponse::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($verifikator)
            ->post(route('app.verification.verify', $response->id), [
                'status' => SurveyResponse::STATUS_NEED_REVISION,
                'note' => 'Mohon lengkapi data kesehatan responden.',
            ])
            ->assertRedirect(route('app.verification.index'));

        $response->refresh();

        $this->assertSame(SurveyResponse::STATUS_NEED_REVISION, $response->status);
        $this->assertSame($verifikator->id, $response->verified_by);
        $this->assertNull($response->verified_at);
        $this->assertDatabaseHas('verification_logs', [
            'survey_response_id' => $response->id,
            'status' => SurveyResponse::STATUS_NEED_REVISION,
            'note' => 'Mohon lengkapi data kesehatan responden.',
            'verified_by' => $verifikator->id,
        ]);

        Queue::assertPushed(SyncDashboardFacts::class);
    }

    public function test_admin_and_surveyor_can_export_csv_but_verifikator_cannot(): void
    {
        $this->seedSurveyPrerequisites();
        $admin = $this->userWithRole('administrator');
        $surveyor = $this->userWithRole('surveyor');
        $verifikator = $this->userWithRole('verifikator');
        $this->surveyResponseFor($surveyor, ['questionnaire_number' => 'EXP-001']);

        $this->actingAs($admin)
            ->get(route('app.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($surveyor)
            ->get(route('app.export', ['format' => 'csv']))
            ->assertOk();

        $this->actingAs($verifikator)
            ->get(route('app.export', ['format' => 'csv']))
            ->assertForbidden();
    }

    public function test_admin_can_open_activity_log(): void
    {
        $admin = $this->userWithRole('administrator');
        $surveyor = $this->userWithRole('surveyor');
        $verifikator = $this->userWithRole('verifikator');

        $this->actingAs($admin)
            ->get(route('app.activity-logs.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas');

        $this->actingAs($surveyor)
            ->get(route('app.activity-logs.index'))
            ->assertForbidden();

        $this->actingAs($verifikator)
            ->get(route('app.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_region_village_search_requires_auth_and_returns_limited_json(): void
    {
        $this->seedSurveyPrerequisites();
        $admin = $this->userWithRole('administrator');

        $this->createManyVillages('Jayapura Test', 55);

        $this->get(route('app.wilayah.villages.search', ['q' => 'Jayapura']))
            ->assertRedirect(route('login'));

        $response = $this->actingAs($admin)
            ->getJson(route('app.wilayah.villages.search', ['q' => 'Jayapura']))
            ->assertOk()
            ->json();

        $this->assertLessThanOrEqual(50, count($response));
        $this->assertArrayHasKey('label', $response[0]);
    }

    private function seedSurveyPrerequisites(): void
    {
        $this->roles();

        Survey::create([
            'title' => 'Survey Lansia Test',
            'description' => 'Test survey',
            'version' => '1.0',
            'is_active' => true,
        ]);

        if (! Region::where('type', 'village')->exists()) {
            $province = Region::create(['name' => 'Papua', 'type' => 'province', 'code' => '91', 'is_active' => true]);
            $city = Region::create(['name' => 'Kota Test', 'type' => 'city', 'code' => '91.01', 'parent_id' => $province->id, 'is_active' => true]);
            $district = Region::create(['name' => 'Distrik Test', 'type' => 'district', 'code' => '91.01.01', 'parent_id' => $city->id, 'is_active' => true]);
            Region::create(['name' => 'Kampung Test', 'type' => 'village', 'code' => '91.01.01.001', 'parent_id' => $district->id, 'is_active' => true]);
        }
    }

    private function roles(): void
    {
        foreach (['administrator', 'surveyor', 'verifikator'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $this->roles();

        $plainPassword = $attributes['password'] ?? 'password';
        unset($attributes['password']);

        $user = User::factory()->create(array_merge([
            'username' => fake()->unique()->userName(),
            'password' => bcrypt($plainPassword),
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    private function village(): Region
    {
        return Region::where('type', 'village')->firstOrFail();
    }

    private function surveyResponseFor(User $surveyor, array $attributes = []): SurveyResponse
    {
        $village = $this->village();
        $respondent = Respondent::create([
            'full_name' => $attributes['respondent_name'] ?? 'Responden Test',
            'gender' => 'male',
            'age' => 70,
            'region_id' => $village->id,
        ]);

        $response = SurveyResponse::create(array_merge([
            'survey_id' => Survey::firstOrFail()->id,
            'respondent_id' => $respondent->id,
            'questionnaire_number' => 'SR-'.$surveyor->id.'-'.uniqid(),
            'surveyor_id' => $surveyor->id,
            'region_id' => $village->id,
            'interview_date' => now()->toDateString(),
            'status' => SurveyResponse::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ], $attributes));

        $response->forceFill([
            'created_by' => $surveyor->id,
            'updated_by' => $surveyor->id,
        ])->save();

        return $response;
    }

    private function createManyVillages(string $namePrefix, int $count): void
    {
        $province = Region::firstOrCreate(
            ['code' => '99'],
            ['name' => 'Papua Macro', 'type' => 'province', 'is_active' => true]
        );
        $city = Region::firstOrCreate(
            ['code' => '99.01'],
            ['name' => 'Kota Macro', 'type' => 'city', 'parent_id' => $province->id, 'is_active' => true]
        );
        $district = Region::firstOrCreate(
            ['code' => '99.01.01'],
            ['name' => 'Distrik Macro', 'type' => 'district', 'parent_id' => $city->id, 'is_active' => true]
        );

        for ($i = 1; $i <= $count; $i++) {
            Region::create([
                'name' => $namePrefix.' '.$i,
                'type' => 'village',
                'code' => '99.01.01.'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'parent_id' => $district->id,
                'is_active' => true,
            ]);
        }
    }
}
