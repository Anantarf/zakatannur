<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AutocompleteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $types = $request->query('types')
            ? explode(',', $request->query('types'))
            : [];

        $data = AutocompleteService::getAutocompleteData($types);

        return response()->json($data);
    }
}
