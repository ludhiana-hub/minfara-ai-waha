<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;

class TrainingMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingMaterial::query();

        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $materials = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('cms.training-materials.index', compact('materials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|in:chat_export,competitor_research,sales_technique,other',
            'content'     => 'required|string',
            'source_note' => 'nullable|string|max:255',
        ]);

        TrainingMaterial::create($validated + ['is_active' => true]);

        return redirect()->route('cms.training-materials.index')->with('success', 'Materi latihan ditambahkan.');
    }

    public function toggle(TrainingMaterial $trainingMaterial)
    {
        $trainingMaterial->update(['is_active' => !$trainingMaterial->is_active]);

        return response()->json(['success' => true, 'is_active' => $trainingMaterial->is_active]);
    }

    public function destroy(TrainingMaterial $trainingMaterial)
    {
        $trainingMaterial->delete();

        return redirect()->route('cms.training-materials.index')->with('success', 'Materi latihan dihapus.');
    }
}
