<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::orderBy('naam')->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create', [
            'colors' => Category::colors(),
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => ['required', 'string', 'max:255', Rule::unique('categories', 'naam')],
            'kleur' => ['required', Rule::in(array_keys(Category::colors()))],
        ]);

        $validated['slug'] = $this->uniqueSlug($validated['naam']);
        $validated['is_active'] = true;

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categorie aangemaakt.');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', [
            'category' => $category,
            'colors' => Category::colors(),
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'naam' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'naam')->ignore($category->id),
            ],
            'kleur' => ['required', Rule::in(array_keys(Category::colors()))],
        ]);

        // The slug is the stable reference used by existing tickets, so it
        // intentionally does not change when the display name is updated.
        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categorie bijgewerkt.');
    }

    /**
     * Activate or deactivate the specified category.
     */
    public function toggleActive(Category $category)
    {
        if ($category->is_active && Category::where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Er moet minimaal één actieve categorie blijven bestaan.');
        }

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with(
            'success',
            $category->is_active ? 'Categorie geactiveerd.' : 'Categorie gedeactiveerd.',
        );
    }

    /**
     * Build a unique slug for a category name.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '_');

        if (blank($base)) {
            $base = 'categorie';
        }

        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'_'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
