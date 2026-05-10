<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\WhatsappLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = WhatsappLog::query();

        if ($request->mode && $request->mode !== 'all') {
            $query->where('mode', $request->mode);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('from_number', 'like', "%$search%")
                  ->orWhere('contact_name', 'like', "%$search%");
            });
        }

        if ($request->date_from) {
            $query->whereDate('responded_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('responded_at', '<=', $request->date_to);
        }

        $logs = $query->latest('responded_at')->paginate(20)->withQueryString();

        $todayStats = WhatsappLog::whereDate('responded_at', today())
            ->selectRaw('mode, count(*) as total')
            ->groupBy('mode')
            ->pluck('total', 'mode');

        $todayTotal = $todayStats->sum();

        return view('cms.log.index', compact('logs', 'todayStats', 'todayTotal'));
    }

    public function show(int $id)
    {
        $log = WhatsappLog::findOrFail($id);
        return view('cms.log.show', compact('log'));
    }

    public function clear(Request $request)
    {
        $type = $request->input('type', 'old');

        if ($type === 'all') {
            WhatsappLog::truncate();
        } else {
            WhatsappLog::where('responded_at', '<', now()->subDays(30))->delete();
        }

        return response()->json(['success' => true, 'message' => 'Log berhasil dihapus.']);
    }
}
