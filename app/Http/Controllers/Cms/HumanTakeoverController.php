<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use App\Models\PausedContact;
use App\Models\WhatsappLog;
use Illuminate\Http\Request;

class HumanTakeoverController extends Controller
{
    public function index()
    {
        $active  = PausedContact::where('paused_until', '>', now())
            ->orderBy('paused_until')
            ->get();

        $recentLogs = WhatsappLog::where('mode', 'human_takeover')
            ->orderByDesc('responded_at')
            ->limit(20)
            ->get();

        $pauseMinutes = (int) BotConfig::get('human_takeover_minutes', '10');

        return view('cms.human-takeover.index', compact('active', 'recentLogs', 'pauseMinutes'));
    }

    public function manualPause(Request $request)
    {
        $request->validate([
            'number'  => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'minutes' => ['required', 'integer', 'in:10,30,60,120'],
        ]);

        $number  = preg_replace('/\D/', '', $request->number);
        $minutes = (int) $request->minutes;

        PausedContact::pauseContact($number, null, $minutes);

        return back()->with('success', "Nomor {$number} dijeda selama {$minutes} menit.");
    }

    public function resume(string $number)
    {
        $number = preg_replace('/\D/', '', $number);
        PausedContact::where('from_number', $number)->delete();

        return back()->with('success', "Jeda untuk nomor {$number} diakhiri.");
    }
}
