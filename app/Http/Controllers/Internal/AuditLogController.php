<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Admin\ZakkyAdminInsightService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, ZakkyAdminInsightService $zakkyInsightService)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = AuditLog::with('actorUser');

        if ($sortBy === 'petugas') {
            $query->leftJoin('users', 'audit_logs.actor_user_id', '=', 'users.id')
                ->select('audit_logs.*') // Avoid column name collisions
                ->orderBy('users.name', $sortDir);
        } elseif (in_array($sortBy, ['action', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate(50)->appends($request->query());
        $totalLogs = AuditLog::query()->count();
        $latestLog = AuditLog::query()->latest('created_at')->first();
        $zakkyInsight = $zakkyInsightService->auditLogInsight();

        return view('internal.audit_logs.index', compact('logs', 'sortBy', 'sortDir', 'totalLogs', 'latestLog', 'zakkyInsight'));
    }
}
