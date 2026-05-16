<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\PublicSummaryService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GuestSummaryController extends Controller
{
    public function index(Request $request, PublicSummaryService $publicSummaryService)
    {
        try {
            $year = $publicSummaryService->resolveYear($request->integer('year'));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => 'Parameter year tidak valid.'], 422);
        }

        return response()->json($publicSummaryService->publicSummaryResponse($year));
    }
}
