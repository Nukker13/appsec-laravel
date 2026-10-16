<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LabProgress;
use App\Services\HintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HintController extends Controller
{
    public function __construct(private readonly HintService $hints) {}

    public function index(Request $request, Lab $lab): JsonResponse
    {
        // D1: only the current user's own progress row is ever read.
        $progress = LabProgress::query()
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->where('lab_id', $lab->id)
            ->first();

        if ($progress === null) {
            return response()->json(['hints' => []]);
        }

        $body = ['hints' => $this->hints->due($progress)];
        $next = $this->hints->nextAt($progress);
        if ($next !== null) {
            $body['next_at'] = $next->toIso8601String();
        }

        return response()->json($body);
    }
}
