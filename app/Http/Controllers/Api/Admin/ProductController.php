<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::orderBy('created_at', 'desc')->get();

        return response()->json($products->map->toArray()->values());
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->toArray());
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->toModelAttributes());

        return response()->json($product->toArray(), 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->toModelAttributes());

        return response()->json($product->fresh()->toArray());
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
