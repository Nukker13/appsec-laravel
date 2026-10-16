<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminMetrics;
use Illuminate\Contracts\View\View;

class LabAnalyticsController extends Controller
{
    public function index(AdminMetrics $metrics): View
    {
        return view('admin.labs', ['rows' => $metrics->labAnalytics(), 'metrics' => $metrics]);
    }
}
