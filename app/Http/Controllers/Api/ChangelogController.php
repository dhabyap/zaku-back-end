<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Http\Requests\StoreChangelogRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 50);

        $changelogs = Changelog::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $changelogs->map(fn (Changelog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'description' => $log->description,
                'author' => $log->author ?? 'Zaku Team',
                'version' => $log->version,
                'status' => $log->status,
                'issues' => $log->issues,
                'created_at' => $log->created_at?->toISOString(),
            ]),
            'pagination' => [
                'current_page' => $changelogs->currentPage(),
                'last_page' => $changelogs->lastPage(),
                'per_page' => $changelogs->perPage(),
                'total' => $changelogs->total(),
            ],
        ], 'Daftar changelog berhasil diambil');
    }

    public function store(StoreChangelogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author'] = $data['author'] ?? $request->user()?->name ?? 'Zaku Team';

        $changelog = Changelog::create($data);

        return $this->successResponse([
            'id' => $changelog->id,
            'title' => $changelog->title,
            'description' => $changelog->description,
            'author' => $changelog->author ?? 'Zaku Team',
            'version' => $changelog->version,
            'status' => $changelog->status,
            'issues' => $changelog->issues,
            'created_at' => $changelog->created_at?->toISOString(),
        ], 'Changelog berhasil ditambahkan', 201);
    }
}
