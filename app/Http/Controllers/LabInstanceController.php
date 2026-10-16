<?php

namespace App\Http\Controllers;

use App\Jobs\LaunchLab;
use App\Models\Lab;
use App\Models\User;
use App\Services\InstanceRegistry;
use App\Services\InstanceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabInstanceController extends Controller
{
    public function __construct(private readonly InstanceResolver $resolver, private readonly InstanceRegistry $registry) {}

    public function launch(Request $request, Lab $lab): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $project = $this->resolver->project($user, $lab);

        if (! in_array($this->registry->status($project), ['starting', 'ready'], true)) {
            $this->registry->setStatus($project, 'pending');
        }

        LaunchLab::dispatch($user, $lab);

        return response()->json(['status' => $this->registry->status($project)], 202);
    }

    public function status(Request $request, Lab $lab): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $project = $this->resolver->project($user, $lab);
        $status = $this->registry->status($project);

        if ($status !== 'ready') {
            return response()->json(['status' => $status]);
        }

        return response()->json([
            'status' => $status,
            'url' => 'http://'.str_replace('_', '-', $project).'.'.config()->string('labs.host_suffix'),
        ]);
    }
}
