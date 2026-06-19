<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\FaqRequest;
use App\Models\FaqMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = FaqMenu::query();

        if ($request->filter === 'active') {
            $query->where('is_active', true);
        } elseif ($request->filter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->filter === 'parent') {
            $query->whereNull('parent_command');
        } elseif ($request->filter === 'sub') {
            $query->whereNotNull('parent_command');
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('command', 'like', "%$search%")
                  ->orWhere('title', 'like', "%$search%");
            });
        }

        $faqs = $query->orderBy('sort_order')->orderBy('command')->get();

        return view('cms.faq.index', compact('faqs'));
    }

    public function create()
    {
        $parents = FaqMenu::whereNull('parent_command')->orderBy('sort_order')->get();
        return view('cms.faq.form', compact('parents'));
    }

    public function store(FaqRequest $request)
    {
        FaqMenu::create([
            'command'        => $request->command,
            'title'          => $request->title,
            'content'        => $request->content,
            'parent_command' => $request->parent_command ?: null,
            'sort_order'     => $request->sort_order ?? 0,
            'is_active'      => $request->has('is_active'),
        ]);

        Cache::forget('faq_ai_context');

        return redirect()->route('cms.faq.index')
            ->with('success', 'Menu FAQ berhasil ditambahkan.');
    }

    public function edit(FaqMenu $faq)
    {
        $parents = FaqMenu::whereNull('parent_command')
            ->where('id', '!=', $faq->id)
            ->orderBy('sort_order')
            ->get();
        return view('cms.faq.form', compact('faq', 'parents'));
    }

    public function update(FaqRequest $request, FaqMenu $faq)
    {
        $faq->update([
            'command'        => $request->command,
            'title'          => $request->title,
            'content'        => $request->content,
            'parent_command' => $request->parent_command ?: null,
            'sort_order'     => $request->sort_order ?? 0,
            'is_active'      => $request->has('is_active'),
        ]);

        Cache::forget('faq_ai_context');

        return redirect()->route('cms.faq.index')
            ->with('success', 'Menu FAQ berhasil diperbarui.');
    }

    public function destroy(FaqMenu $faq)
    {
        $faq->delete();
        Cache::forget('faq_ai_context');
        return response()->json(['success' => true, 'message' => 'Menu FAQ berhasil dihapus.']);
    }

    public function toggle(FaqMenu $faq)
    {
        $faq->update(['is_active' => !$faq->is_active]);
        Cache::forget('faq_ai_context');
        return response()->json([
            'success'   => true,
            'is_active' => $faq->is_active,
            'message'   => 'Status menu berhasil diubah.',
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'array', 'order.*' => 'integer']);
        $order = $request->input('order', []);
        foreach ($order as $index => $id) {
            FaqMenu::where('id', $id)->update(['sort_order' => $index]);
        }
        return response()->json(['success' => true]);
    }
}
