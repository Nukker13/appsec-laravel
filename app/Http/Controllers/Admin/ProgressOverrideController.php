<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgressOverrideController extends Controller
{
    public function reset(Request $request, User $user, Lab $lab): RedirectResponse
    {
        $reason = $this->reason($request);

        DB::transaction(function () use ($request, $user, $lab, $reason): void {
            $previous = $this->current($user, $lab);

            LabProgress::query()->where('user_id', $user->id)->where('lab_id', $lab->id)->delete();

            $this->audit($request, $user, $lab, 'progress_reset', $reason, $previous);
        });

        return back()->with('status', "Progress for {$user->name} on {$lab->name} was reset.");
    }

    public function complete(Request $request, User $user, Lab $lab): RedirectResponse
    {
        $reason = $this->reason($request);

        DB::transaction(function () use ($request, $user, $lab, $reason): void {
            $previous = $this->current($user, $lab);
            $now = now();

            LabProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'lab_id' => $lab->id],
                ['status' => 'completed', 'completed_at' => $now] + ($previous === null ? ['started_at' => $now] : []),
            );

            $this->audit($request, $user, $lab, 'progress_complete', $reason, $previous);
        });

        return back()->with('status', "{$lab->name} was marked complete for {$user->name}.");
    }

    private function reason(Request $request): string
    {
        /** @var array{reason: string} $data */
        $data = $request->validate(['reason' => ['required', 'string']]);

        return $data['reason'];
    }

    /**
     * @return array{status: string, started_at: string|null, completed_at: string|null}|null
     */
    private function current(User $user, Lab $lab): ?array
    {
        $row = LabProgress::query()->where('user_id', $user->id)->where('lab_id', $lab->id)->first();

        return $row === null ? null : [
            'status' => $row->status,
            'started_at' => $row->started_at->toIso8601String(),
            'completed_at' => $row->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{status: string, started_at: string|null, completed_at: string|null}|null  $previous
     */
    private function audit(Request $request, User $user, Lab $lab, string $action, string $reason, ?array $previous): void
    {
        AdminAction::create([
            'admin_id' => $request->user()?->id,
            'target_user_id' => $user->id,
            'lab_id' => $lab->id,
            'action' => $action,
            'reason' => $reason,
            'meta' => $previous,
        ]);
    }
}
