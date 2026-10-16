<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HintController extends Controller
{
    public function index(Request $request, Lab $lab): JsonResponse
    {
        throw new \LogicException('not implemented');
    }
}
