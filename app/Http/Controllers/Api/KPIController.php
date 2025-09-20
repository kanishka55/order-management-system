<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KPIController extends Controller
{
    public function __construct(private KPIService $kpiService)
    {

    }

    public function daily(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Validate date format
        try {
            Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format.'], 400);
        }

        $kpis = $this->kpiService->getDailyKPIs($date);
        return response()->json($kpis);
    }

    public function leaderboard(Request $request) 
    {
        $limit = min($request->get('limit', 10), 50); //max 50

        $learderboard = $this->kpiService->getTopCustomers($limit);

        return response()-> json([
            'leaderboard' => $learderboard,
            'generated_at' => now()->toISOString(),
        ]);
    }
}
