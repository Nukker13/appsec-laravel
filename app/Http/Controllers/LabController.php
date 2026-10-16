<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request): View
    {
        throw new \LogicException('not implemented');
    }

    public function show(Lab $lab): View
    {
        throw new \LogicException('not implemented');
    }
}
