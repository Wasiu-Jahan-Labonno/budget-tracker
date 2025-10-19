<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;

use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return Category::where('user_id', $request->user()->id)
            ->orderBy('type')->orderBy('name')->get();
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create([
            'user_id' => $request->user()->id,
            'name'    => $request->validated()['name'],
            'type'    => $request->validated()['type'],
        ]);
        return response()->json($category, 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {   
       
        abort_unless($category->user_id === $request->user()->id, 403);
        $category->update($request->validated());
        return $category;
    }
    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $category->delete();
        return response()->json(['deleted' => true]);
    }
}
