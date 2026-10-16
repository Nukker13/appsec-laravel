<?php

namespace Tests\Unit;

use App\Models\Lab;
use App\Services\FlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FlagService $flags;

    private Lab $lab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLabs();
        $this->lab = $this->lab('osci');
        $this->flags = app(FlagService::class);
    }

    /** Issue a flag and read it back the way a student would: from the instance flag file. */
    private function issue(string $project, ?Lab $lab = null): string
    {
        $lab ??= $this->lab;
        $this->flags->issue($project, $lab);
        $file = $this->flags->flagFile($project, $lab);
        $this->assertFileExists($file);

        return trim((string) file_get_contents($file));
    }

    public function test_issue_then_verify_is_true(): void
    {
        $flag = $this->issue('lab_osci');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $flag, 'A flag is 32 random bytes in hex.');
        $this->assertTrue($this->flags->verify('lab_osci', $flag));
    }

    public function test_flag_file_path_is_absolute_and_stable(): void
    {
        $path = $this->flags->flagFile('lab_osci', $this->lab);

        $this->assertStringStartsWith('/', $path);
        $this->assertStringEndsWith('flag.txt', $path);
        $this->assertSame($path, $this->flags->flagFile('lab_osci', $this->lab));
    }

    public function test_wrong_flag_is_false(): void
    {
        $flag = $this->issue('lab_osci');

        $this->assertFalse($this->flags->verify('lab_osci', str_repeat('0', 64)));
        $this->assertFalse($this->flags->verify('lab_osci', ''));
        $this->assertFalse($this->flags->verify('lab_osci', substr($flag, 0, 63)));
        $this->assertFalse($this->flags->verify('lab_osci', strtoupper($flag)));
        $this->assertFalse($this->flags->verify('lab_osci', $flag.'0'));
    }

    public function test_nothing_issued_means_false(): void
    {
        $this->assertFalse($this->flags->verify('lab_osci', ''));
        $this->assertFalse($this->flags->verify('lab_osci', 'anything'));
    }

    public function test_only_the_sha256_is_stored_in_cache(): void
    {
        $flag = $this->issue('lab_osci');

        $this->assertSame(hash('sha256', $flag), Cache::get('lab:lab_osci:flag'));
        $this->assertNotSame($flag, Cache::get('lab:lab_osci:flag'));
    }

    public function test_verification_uses_hash_equals(): void
    {
        // hash_equals is a timing property that cannot be observed through behaviour: check the source.
        $source = (string) file_get_contents(app_path('Services/FlagService.php'));

        $this->assertStringContainsString('hash_equals(', $source);
    }

    public function test_instances_have_independent_flags(): void
    {
        $dom = $this->lab('dom-xss');
        $one = $this->issue('lab_osci');
        $two = $this->issue('lab_dom_xss', $dom);

        $this->assertNotSame($one, $two);
        $this->assertTrue($this->flags->verify('lab_osci', $one));
        $this->assertTrue($this->flags->verify('lab_dom_xss', $two));
        $this->assertFalse($this->flags->verify('lab_osci', $two));
        $this->assertFalse($this->flags->verify('lab_dom_xss', $one));

        $this->flags->forget('lab_osci');

        $this->assertFalse($this->flags->verify('lab_osci', $one));
        $this->assertTrue($this->flags->verify('lab_dom_xss', $two), 'Forgetting one instance affected another.');
    }

    public function test_forgotten_flag_is_false(): void
    {
        $flag = $this->issue('lab_osci');

        $this->flags->forget('lab_osci');

        $this->assertFalse($this->flags->verify('lab_osci', $flag));
        $this->flags->forget('lab_osci');
        $this->flags->forget('lab_never_issued');
    }

    public function test_issuing_again_replaces_the_flag(): void
    {
        $old = $this->issue('lab_osci');
        $new = $this->issue('lab_osci');

        $this->assertNotSame($old, $new);
        $this->assertTrue($this->flags->verify('lab_osci', $new));
        $this->assertFalse($this->flags->verify('lab_osci', $old));
    }

    public function test_flag_stops_verifying_after_the_instance_lifetime(): void
    {
        config()->set('labs.max_minutes', 120);
        $this->travelTo(now()->startOfSecond());
        $flag = $this->issue('lab_osci');

        $this->travel(119)->minutes();
        $this->assertTrue($this->flags->verify('lab_osci', $flag));

        $this->travel(2)->minutes();
        $this->assertFalse($this->flags->verify('lab_osci', $flag));
    }
}
