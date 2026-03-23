<?php

namespace App\Http\Controllers;

use App\Http\Responses\MapsServiceErrors;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use MapsServiceErrors;

    protected function jsonFromService(array $result): JsonResponse
    {
        return response()->json($result['json'], $result['http_status']);
    }
}
