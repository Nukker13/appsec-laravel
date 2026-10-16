<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\User;
use App\Services\AdminMetrics;
use Illuminate\Contracts\View\View;

class ProgressController extends Controller
{
    public function index(AdminMetrics $metrics): View
    {
        return view('admin.progress', [
            'users' => User::query()->orderBy('name')->orderBy('id')->get(),
            'labs' => Lab::query()->orderBy('sort')->orderBy('id')->get(),
            'cells' => $metrics->cells(),
            'metrics' => $metrics,
        ]);
    }
}
