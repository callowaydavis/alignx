<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Tag::class);

        $tags = Tag::query()->withCount('components')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Tag::class);

        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ]);

        Tag::query()->create(['name' => $request->string('name')->trim()]);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag created.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name,'.$tag->id],
        ]);

        $tag->update(['name' => $request->string('name')->trim()]);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag renamed.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->components()->detach();
        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag deleted.');
    }
}
