<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $totalFaq    = FaqMenu::count();
        $activeFaq   = FaqMenu::active()->count();
        $inactiveFaq = $totalFaq - $activeFaq;

        $todayLogs   = WhatsappLog::whereDate('responded_at', today())->count();
        $modeCounts  = WhatsappLog::whereDate('responded_at', today())
            ->selectRaw('mode, count(*) as total')
            ->groupBy('mode')
            ->pluck('total', 'mode');

        $recentLogs = WhatsappLog::latest('responded_at')->take(10)->get();

        $wahaStatus = $this->getWahaStatus();

        $aiEnabled = BotConfig::getBool('ai_enabled', true);

        return view('cms.dashboard', compact(
            'totalFaq', 'activeFaq', 'inactiveFaq',
            'todayLogs', 'modeCounts', 'recentLogs',
            'wahaStatus', 'aiEnabled'
        ));
    }

    private function getWahaStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => config('services.waha.api_key')])
                ->get(config('services.waha.url') . '/api/sessions/default');

            if ($response->ok()) {
                $data = $response->json();
                return [
                    'connected' => $data['status'] === 'WORKING',
                    'status'    => $data['status'] ?? 'UNKNOWN',
                    'phone'     => $data['me']['id'] ?? null,
                ];
            }
        } catch (\Exception $e) {
        }

        return ['connected' => false, 'status' => 'DISCONNECTED', 'phone' => null];
    }
}
