<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('activity_log')
            ->leftJoin('users', function ($join): void {
                $join->on('activity_log.causer_id', '=', 'users.id')
                    ->where('activity_log.causer_type', '=', 'App\\Models\\User');
            })
            ->select([
                'activity_log.*',
                'users.name as causer_name',
                'users.email as causer_email',
            ]);

        if ($request->filled('event')) {
            $query->where('activity_log.event', $request->event);
        }

        if ($request->filled('log_name')) {
            $query->where('activity_log.log_name', $request->log_name);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search): void {
                $q->where('activity_log.description', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $logs = $query
            ->orderByDesc('activity_log.created_at')
            ->paginate(25)
            ->withQueryString();

        $events = DB::table('activity_log')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $logNames = DB::table('activity_log')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return view('app.activity-logs.index', compact('logs', 'events', 'logNames'));
    }
}
