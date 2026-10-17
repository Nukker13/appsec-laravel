<?php

namespace Tests\Unit;

use App\Services\InstanceRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InstanceRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(InstanceRegistry::RUNNING_KEY);
    }

    public function test_reserve_takes_slots_up_to_max_and_refuses_beyond(): void
    {
        $registry = app(InstanceRegistry::class);

        $this->assertTrue($registry->reserve('lab_osci_u1', 'osci', 2));
        $this->assertTrue($registry->reserve('lab_rbac_u2', 'rbac', 2));
        $this->assertSame(['lab_osci_u1' => 'osci', 'lab_rbac_u2' => 'rbac'], $registry->running());

        $this->assertFalse($registry->reserve('lab_ssrf_u3', 'ssrf', 2));
        $this->assertArrayNotHasKey('lab_ssrf_u3', $registry->running());
        $this->assertCount(2, $registry->running());
    }

    public function test_reserve_is_idempotent_for_an_already_registered_project_when_full(): void
    {
        $registry = app(InstanceRegistry::class);
        $registry->reserve('lab_osci_u1', 'osci', 2);
        $registry->reserve('lab_rbac_u2', 'rbac', 2);

        $this->assertTrue($registry->reserve('lab_osci_u1', 'osci', 2));
        $this->assertCount(2, $registry->running());
        $this->assertSame('osci', $registry->running()['lab_osci_u1']);
    }

    public function test_unregister_frees_a_reserved_slot(): void
    {
        $registry = app(InstanceRegistry::class);
        $registry->reserve('lab_osci_u1', 'osci', 1);
        $this->assertFalse($registry->reserve('lab_rbac_u2', 'rbac', 1));

        $registry->unregister('lab_osci_u1');

        $this->assertTrue($registry->reserve('lab_rbac_u2', 'rbac', 1));
    }
}
