<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $entries = KnowledgeBase::orderBy('title')->get();
        return view('internal.knowledge_base.index', compact('entries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:knowledge_bases,slug'],
            'title' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'source_label' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['keywords'] = $this->parseKeywords($validated['keywords'] ?? '');

        KnowledgeBase::create($validated);

        return redirect()->route('internal.knowledge-base.index')->with('status', 'Data referensi berhasil ditambahkan.');
    }

    public function edit(KnowledgeBase $knowledge_base)
    {
        $entries = KnowledgeBase::orderBy('title')->get();
        return view('internal.knowledge_base.index', [
            'entries' => $entries,
            'editing' => $knowledge_base,
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledge_base)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', Rule::unique('knowledge_bases')->ignore($knowledge_base->id)],
            'title' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'source_label' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['keywords'] = $this->parseKeywords($validated['keywords'] ?? '');

        $knowledge_base->update($validated);

        return redirect()->route('internal.knowledge-base.index')->with('status', 'Data referensi berhasil diperbarui.');
    }

    public function destroy(KnowledgeBase $knowledge_base)
    {
        $knowledge_base->delete();
        return redirect()->route('internal.knowledge-base.index')->with('status', 'Data referensi berhasil dihapus.');
    }

    private function parseKeywords(string $keywordsString): array
    {
        if (empty(trim($keywordsString))) {
            return [];
        }
        
        $keywords = array_map('trim', explode(',', $keywordsString));
        return array_values(array_filter($keywords, fn($k) => $k !== ''));
    }
}
