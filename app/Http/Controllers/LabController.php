<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LabProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['category' => ['nullable', 'in:frontend,backend']]);
        $category = $data['category'] ?? null;

        $labs = Lab::query()
            ->when($category, fn ($q, string $c) => $q->where('category', $c))
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        // D1: status comes from the current user's own progress rows only.
        $status = LabProgress::query()
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->pluck('status', 'lab_id');

        return view('labs-index', [
            'labs' => $labs,
            'category' => $category,
            'status' => $status,
        ]);
    }

    public function show(Lab $lab): View
    {
        return view('lab', ['lab' => $lab]);
    }

    public function open(Lab $lab): View
    {
        return view('lab-open', ['lab' => $lab]);
    }
}
