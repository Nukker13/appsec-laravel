<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProgressOverrideController extends Controller
{
    public function reset(Request $request, User $user, Lab $lab): RedirectResponse
    {
        throw new \LogicException('not implemented');
    }

    public function complete(Request $request, User $user, Lab $lab): RedirectResponse
    {
        throw new \LogicException('not implemented');
    }
}
