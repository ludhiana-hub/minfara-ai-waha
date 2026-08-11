<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use App\Services\DocumentExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function store(Request $request, DocumentExtractionService $extractor)
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'category'    => 'required|in:chat_export,competitor_research,sales_technique,other',
            'content'     => 'nullable|string',
            'source_note' => 'nullable|string|max:255',
            'files'       => 'nullable|array',
            'files.*'     => 'file|mimes:pdf,docx,txt,csv,jpg,jpeg,png,webp|max:10240',
        ]);

        $hasFiles = $request->hasFile('files');

        if (!$hasFiles && !filled($validated['content'] ?? null)) {
            return back()->withInput()->with('error', 'Isi materi lewat teks atau upload minimal satu file.');
        }

        $created = 0;
        $errors  = [];

        if ($hasFiles) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                try {
                    $text = $extractor->extract($file);
                } catch (\RuntimeException $e) {
                    $errors[] = "{$originalName}: {$e->getMessage()}";
                    continue;
                }

                $path = $file->store('training-materials', 'local');

                TrainingMaterial::create([
                    'title'              => filled($validated['title'] ?? null)
                        ? $validated['title'] . ' — ' . $originalName
                        : pathinfo($originalName, PATHINFO_FILENAME),
                    'category'           => $validated['category'],
                    'content'            => $text,
                    'source_note'        => $validated['source_note'] ?? null,
                    'file_path'          => $path,
                    'original_filename'  => $originalName,
                    'is_active'          => true,
                ]);
                $created++;
            }
        } else {
            TrainingMaterial::create([
                'title'       => $validated['title'] ?: 'Tanpa judul',
                'category'    => $validated['category'],
                'content'     => $validated['content'],
                'source_note' => $validated['source_note'] ?? null,
                'is_active'   => true,
            ]);
            $created++;
        }

        $message = "{$created} materi latihan ditambahkan.";
        if (!empty($errors)) {
            $message .= ' Gagal: ' . implode(' | ', $errors);
            return redirect()->route('cms.training-materials.index')->with('warning', $message);
        }

        return redirect()->route('cms.training-materials.index')->with('success', $message);
    }

    public function toggle(TrainingMaterial $trainingMaterial)
    {
        $trainingMaterial->update(['is_active' => !$trainingMaterial->is_active]);

        return response()->json(['success' => true, 'is_active' => $trainingMaterial->is_active]);
    }

    public function destroy(TrainingMaterial $trainingMaterial)
    {
        if ($trainingMaterial->file_path) {
            Storage::disk('local')->delete($trainingMaterial->file_path);
        }

        $trainingMaterial->delete();

        return redirect()->route('cms.training-materials.index')->with('success', 'Materi latihan dihapus.');
    }
}
