<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchSynonym;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSearchSynonymController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = SearchSynonym::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($q)).'%';
                $query->where('keyword', 'like', $like)->orWhere('synonym', 'like', $like);
            })
            ->orderBy('keyword')
            ->orderBy('synonym')
            ->paginate(30)
            ->withQueryString();

        return view('admin.search_synonyms.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('admin.search_synonyms.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        SearchSynonym::query()->create($data);

        return redirect()->route('admin.search-synonyms.index')->with('success', 'Đã thêm synonym.');
    }

    public function edit(SearchSynonym $search_synonym)
    {
        $row = $search_synonym;

        return view('admin.search_synonyms.edit', compact('row'));
    }

    public function update(Request $request, SearchSynonym $search_synonym)
    {
        $data = $this->validatedData($request, $search_synonym->id);
        $search_synonym->update($data);

        return redirect()->route('admin.search-synonyms.index')->with('success', 'Đã cập nhật synonym.');
    }

    public function destroy(SearchSynonym $search_synonym)
    {
        $search_synonym->delete();

        return redirect()->route('admin.search-synonyms.index')->with('success', 'Đã xóa synonym.');
    }

    protected function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $keyword = mb_strtolower(trim((string) $request->input('keyword', '')));
        $synonym = mb_strtolower(trim((string) $request->input('synonym', '')));

        $unique = Rule::unique('search_synonyms')->where(fn ($q) => $q->where('keyword', $keyword)->where('synonym', $synonym));
        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'synonym' => ['required', 'string', 'max:255', $unique],
        ]);

        return [
            'keyword' => $keyword,
            'synonym' => $synonym,
        ];
    }
}

