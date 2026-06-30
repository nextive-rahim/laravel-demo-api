<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * List all categories with their subcategories for the admin question bank.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount('subcategories')
            ->with('subcategories')
            ->latest()
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Create a category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= str($data['name'])->slug()->toString();

        $category = Category::create($data);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Show a single category with its subcategories.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->load('subcategories')->loadCount('subcategories'));
    }

    /**
     * Update a category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Delete a category (cascades to its subcategories and their questions).
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
