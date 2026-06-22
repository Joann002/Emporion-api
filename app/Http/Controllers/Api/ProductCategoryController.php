<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::orderBy('name')->get();
        return ProductCategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = ProductCategory::create($validated);

        return new ProductCategoryResource($category);
    }

    public function show(ProductCategory $productCategory)
    {
        return new ProductCategoryResource($productCategory);
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $productCategory->update($validated);

        return new ProductCategoryResource($productCategory);
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès',
        ]);
    }
}
