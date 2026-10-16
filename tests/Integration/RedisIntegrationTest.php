<?php

namespace Tests\Integration;

use App\Models\Lab;
use App\Services\FlagService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\UsesRealRedis;
use Tests\TestCase;

#[Group('redis')]
class RedisIntegrationTest extends TestCase
{
    use UsesRealRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useRealRedis();
    }

    protected function tearDown(): void
    {
        $this->releaseRealRedis();

        parent::tearDown();
    }

    public function test_flag_expires_with_instance_ttl(): void
    {
        config()->set('labs.max_minutes', 7);
        $lab = new Lab(['slug' => 'osci', 'name' => 'OS Command Injection', 'category' => 'backend']);
        $flags = app(FlagService::class);

        $flags->issue('lab_osci', $lab);
        $file = $flags->flagFile('lab_osci', $lab);
        $flag = trim((string) file_get_contents($file));
        @unlink($file);

        $this->assertTrue($flags->verify('lab_osci', $flag));

        $store = Cache::store('redis')->getStore();
        $ttl = $store->connection()->ttl($store->getPrefix().'lab:lab_osci:flag');

        $this->assertGreaterThan(7 * 60 - 30, $ttl, 'The flag key has no TTL or a much shorter one.');
        $this->assertLessThanOrEqual(7 * 60, $ttl, 'The flag key outlives LAB_MAX_MINUTES.');

        $flags->forget('lab_osci');
        $this->assertFalse($flags->verify('lab_osci', $flag));
        $this->assertSame(-2, $store->connection()->ttl($store->getPrefix().'lab:lab_osci:flag'), 'forget() left the key in Redis.');
    }
}
