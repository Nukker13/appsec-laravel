<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlagController extends Controller
{
    public function store(Request $request, Lab $lab): JsonResponse
    {
        throw new \LogicException('not implemented');
    }
}
