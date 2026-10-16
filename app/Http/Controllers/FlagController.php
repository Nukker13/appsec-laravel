<?php

namespace App\Http\Controllers;

use App\Jobs\StopLab;
use App\Models\FlagAttempt;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlagController extends Controller
{
    public function store(Request $request, Lab $lab, FlagService $flags, InstanceResolver $resolver): JsonResponse
    {
        $validated = $request->validate(['flag' => ['required', 'string', 'max:255']]);

        /** @var User $user */
        $user = $request->user();
        $project = $resolver->project($user, $lab);
        $correct = $flags->verify($project, $validated['flag']);

        DB::transaction(function () use ($user, $lab, $correct): void {
            FlagAttempt::query()->create(['user_id' => $user->getKey(), 'lab_id' => $lab->getKey(), 'is_correct' => $correct]);

            if ($correct) {
                // D1: only this user's row, and a finished lab keeps its original completion time.
                LabProgress::query()
                    ->where('user_id', $user->getKey())
                    ->where('lab_id', $lab->getKey())
                    ->where('status', 'started')
                    ->update(['status' => 'completed', 'completed_at' => now()]);
            }
        });

        if ($correct) {
            StopLab::dispatch($lab->slug, $project);
        }

        return response()->json(['correct' => $correct]);
    }
}
