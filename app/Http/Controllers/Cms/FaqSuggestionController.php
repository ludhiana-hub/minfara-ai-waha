<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\FaqSuggestion;
use Illuminate\Http\Request;

class FaqSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = FaqSuggestion::query();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $suggestions = $query
            ->orderByDesc('high_priority')
            ->orderByDesc('frequency')
            ->paginate(20)
            ->withQueryString();

        $pendingCount     = FaqSuggestion::where('status', 'pending')->count();
        $highPriorityCount = FaqSuggestion::where('status', 'pending')->where('high_priority', true)->count();

        return view('cms.faq-suggestions.index', compact('suggestions', 'pendingCount', 'highPriorityCount'));
    }

    public function approve(FaqSuggestion $faqSuggestion)
    {
        $faqSuggestion->update(['status' => 'approved']);
        return redirect()->route('cms.faq.create', ['title' => $faqSuggestion->question])
            ->with('success', 'Saran FAQ disetujui. Lengkapi konten FAQ di bawah ini.');
    }

    public function reject(FaqSuggestion $faqSuggestion)
    {
        $faqSuggestion->update(['status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'Saran FAQ ditolak.']);
    }
}
