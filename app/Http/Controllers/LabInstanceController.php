<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabInstanceController extends Controller
{
    public function launch(Request $request, Lab $lab): JsonResponse
    {
        throw new \LogicException('not implemented');
    }

    public function status(Request $request, Lab $lab): JsonResponse
    {
        throw new \LogicException('not implemented');
    }
}
