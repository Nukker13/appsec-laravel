<?php

/*
 * Child process of ConcurrentLaunchTest: boots the application like a queue worker would and runs LaunchLab.
 * Usage: php launch_worker.php {user_id} {lab_id} {state_dir} {instances_dir}
 * Configuration (database, redis) comes from the environment set by the parent test.
 */

use App\Contracts\LabRunner;
use App\Jobs\LaunchLab;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\SharedFileLabRunner;

[, $userId, $labId, $stateDir, $instancesDir] = $argv;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['labs.instances_path' => $instancesDir]);
$app->instance(LabRunner::class, new SharedFileLabRunner($stateDir));

LaunchLab::dispatch(User::query()->findOrFail((int) $userId), Lab::query()->findOrFail((int) $labId));
