<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\User;
use App\Services\AdminMetrics;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function show(User $user, AdminMetrics $metrics): View
    {
        return view('admin.user', [
            'student' => $user,
            'labs' => Lab::query()->orderBy('sort')->orderBy('id')->get(),
            'cells' => $metrics->cells($user->id)[$user->id] ?? [],
            'metrics' => $metrics,
        ]);
    }
}
