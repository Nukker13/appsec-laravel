<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        throw new \LogicException('not implemented');
    }

    public function callback(Request $request): RedirectResponse
    {
        throw new \LogicException('not implemented');
    }

    public function logout(Request $request): RedirectResponse
    {
        throw new \LogicException('not implemented');
    }
}
