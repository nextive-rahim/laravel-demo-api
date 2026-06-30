<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Requests\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubcategoryController extends Controller
{
    /**
     * List subcategories, optionally filtered by category.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $subcategories = Subcategory::query()
            ->when($request->integer('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->withCount('questions')
            ->with('category')
            ->latest()
            ->get();

        return SubcategoryResource::collection($subcategories);
    }

    /**
     * Create a subcategory under a category.
     */
    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= str($data['name'])->slug()->toString();

        $subcategory = Subcategory::create($data);

        return (new SubcategoryResource($subcategory->load('category')))->response()->setStatusCode(201);
    }

    /**
     * Show a single subcategory.
     */
    public function show(Subcategory $subcategory): SubcategoryResource
    {
        return new SubcategoryResource($subcategory->load('category')->loadCount('questions'));
    }

    /**
     * Update a subcategory.
     */
    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): SubcategoryResource
    {
        $subcategory->update($request->validated());

        return new SubcategoryResource($subcategory->load('category'));
    }

    /**
     * Delete a subcategory (cascades to its questions).
     */
    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted.']);
    }
}
