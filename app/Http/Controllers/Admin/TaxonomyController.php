<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Support\TaxonomyRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyController extends Controller
{
    public function index(string $taxonomy, TaxonomyRegistry $registry)
    {
        $definition = $registry->get($taxonomy);
        abort_unless($definition, 404);

        return view('admin.taxonomies.index', [
            'taxonomy' => $definition,
            'allTaxonomies' => $registry->relatedTo($taxonomy),
            'terms' => Term::ofTaxonomy($taxonomy)->withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $taxonomy, TaxonomyRegistry $registry)
    {
        abort_unless($registry->get($taxonomy), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Term::create([
            'taxonomy' => $taxonomy,
            'name' => $data['name'],
            'slug' => Str::slug(($data['slug'] ?? null) ?: $data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('status', 'Đã thêm.');
    }

    public function update(Request $request, string $taxonomy, Term $term)
    {
        abort_unless($term->taxonomy === $taxonomy, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $term->update([
            'name' => $data['name'],
            'slug' => Str::slug(($data['slug'] ?? null) ?: $data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('status', 'Đã cập nhật.');
    }

    public function destroy(string $taxonomy, Term $term)
    {
        abort_unless($term->taxonomy === $taxonomy, 404);

        $term->delete();

        return back()->with('status', 'Đã xoá.');
    }
}
