<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LabProgress;
use App\Services\HintService;
use App\Services\InstanceRegistry;
use App\Services\InstanceResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HintController extends Controller
{
    public function __construct(private readonly HintService $hints, private readonly InstanceResolver $resolver, private readonly InstanceRegistry $registry) {}

    public function index(Request $request, Lab $lab): JsonResponse
    {
        // D1: only the current user's own progress row is ever read.
        $progress = LabProgress::query()
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->where('lab_id', $lab->id)
            ->first();

        $launchedAt = null;
        if ($progress !== null && $request->user() !== null) {
            $launchedAt = $this->registry->launchedAt($this->resolver->project($request->user(), $lab));
        }

        if ($progress === null || $launchedAt === null) {
            return response()->json(['hints' => []]);
        }

        $since = Carbon::createFromTimestamp($launchedAt);
        $body = ['hints' => $this->hints->due($progress, $since)];
        $next = $this->hints->nextAt($progress, $since);
        if ($next !== null) {
            $body['next_at'] = $next->toIso8601String();
        }

        return response()->json($body);
    }
}
