<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $changelogs = Changelog::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Changelog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'description' => $log->description,
                'author' => $log->author ?? 'Zaku Team',
                'version' => $log->version,
                'status' => $log->status,
                'issues' => $log->issues,
                'created_at' => $log->created_at?->toISOString(),
            ]);

        return $this->successResponse($changelogs, 'Daftar changelog berhasil diambil');
    }
}
