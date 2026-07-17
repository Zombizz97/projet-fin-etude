<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    /** Récupère toutes les catégories avec leurs topics et posts counts */
    public function index(): JsonResponse
    {
        $categories = ForumCategory::with(['topics' => function ($q) {
            $q->withCount('posts');
        }])->get();

        return response()->json($categories);
    }

    /** Récupère un topic avec ses informations détaillées et ses posts */
    public function show(int $id): JsonResponse
    {
        $topic = ForumTopic::with([
            'category',
            'user',
            'posts' => function ($q) {
                $q->with('user')->orderBy('created_at', 'asc');
            },
        ])->withCount('posts')->findOrFail($id);

        return response()->json($topic);
    }

    /** Liste paginée des posts pour un topic donné */
    public function posts(int $id, Request $request): JsonResponse
    {
        $perPage = (int) ($request->query('per_page', 10));
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $page = (int) ($request->query('page', 1));
        $page = $page > 0 ? $page : 1;

        $topic = ForumTopic::findOrFail($id);

        $paginator = $topic->posts()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
