<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->icon ?? '📌',
                'type' => $cat->type,
                'keywords' => $cat->keywords ?? [],
            ]);

        return $this->successResponse($categories, 'Daftar kategori berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:categories,name',
            'icon' => 'required|string|max:4',
            'type' => 'required|in:income,expense,both',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
        ]);

        $category = Category::create($validated);

        return $this->successResponse([
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'type' => $category->type,
            'keywords' => $category->keywords ?? [],
        ], 'Kategori berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->notFoundResponse('Kategori tidak ditemukan');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50|unique:categories,name,' . $id,
            'icon' => 'sometimes|required|string|max:4',
            'type' => 'sometimes|required|in:income,expense,both',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
        ]);

        $category->update($validated);

        return $this->successResponse([
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'type' => $category->type,
            'keywords' => $category->keywords ?? [],
        ], 'Kategori berhasil diperbarui');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->notFoundResponse('Kategori tidak ditemukan');
        }

        if ($category->transactions()->count() > 0) {
            return $this->errorResponse('Kategori tidak bisa dihapus karena masih digunakan oleh transaksi', 409);
        }

        $category->delete();

        return $this->successResponse(null, 'Kategori berhasil dihapus');
    }
}
