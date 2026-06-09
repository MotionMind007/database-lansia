<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\DashboardCache;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardCacheTest extends TestCase
{
    public function test_remember_returns_callback_result(): void
    {
        $user = $this->makeUser('administrator');
        $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];

        $result = DashboardCache::remember($user, $filters, fn () => ['stats' => ['total' => 42]]);

        $this->assertSame(42, $result['stats']['total']);
    }

    public function test_remember_caches_result_on_second_call(): void
    {
        $user = $this->makeUser('administrator');
        $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return ['computed' => true];
        };

        DashboardCache::remember($user, $filters, $callback);
        DashboardCache::remember($user, $filters, $callback);

        $this->assertSame(1, $callCount);
    }

    public function test_flush_invalidates_cache(): void
    {
        $user = $this->makeUser('administrator');
        $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return ['computed' => true];
        };

        DashboardCache::remember($user, $filters, $callback);
        DashboardCache::flush();
        DashboardCache::remember($user, $filters, $callback);

        $this->assertSame(2, $callCount);
    }

    public function test_graceful_degradation_on_callback_failure(): void
    {
        $user = $this->makeUser('administrator');
        $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];

        $result = DashboardCache::remember($user, $filters, function () {
            throw new \RuntimeException('Database connection lost');
        });

        $this->assertSame(0, $result['stats']['total']);
        $this->assertIsArray($result['questionAnalytics']);
    }

    public function test_zero_ttl_bypasses_cache(): void
    {
        config(['dashboard.cache_ttl' => 0]);

        $user = $this->makeUser('administrator');
        $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return ['fresh' => true];
        };

        DashboardCache::remember($user, $filters, $callback);
        DashboardCache::remember($user, $filters, $callback);

        $this->assertSame(2, $callCount);
    }

    private function makeUser(string $role): User
    {
        $user = new User(['is_active' => true]);
        $user->id = 1;

        $roleModel = Role::make(['name' => $role, 'guard_name' => 'web']);
        $user->setRelation('roles', collect([$roleModel]));

        return $user;
    }
}
