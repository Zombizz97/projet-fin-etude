<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumPostVote;
use App\Models\ForumTopic;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ForumController extends Controller
{
    private function getOptionalUserId(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');
        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        $token = substr($authHeader, 7);
        $secret = Config::get('services.jwt.secret');
        if (! $secret) {
            return null;
        }
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            return $decoded->sub ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadUserVotes($posts, ?int $userId): void
    {
        if (! $userId) {
            return;
        }
        $postIds = $posts->pluck('id');
        $userVotes = ForumPostVote::whereIn('post_id', $postIds)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('post_id');
        foreach ($posts as $post) {
            $uv = $userVotes->get($post->id);
            $post->setAttribute('user_vote', $uv ? $uv->vote : null);
        }
    }

    /** Récupère toutes les catégories avec leurs topics et posts counts */
    public function index(): JsonResponse
    {
        $categories = ForumCategory::with(['topics' => function ($q) {
            $q->withCount('posts');
        }])->get();

        return response()->json($categories);
    }

    /** Crée un nouveau sujet */
    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:forum_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $topic = ForumTopic::create([
            'category_id' => $validated['category_id'],
            'user_id' => $user->id,
            'title' => $validated['title'],
        ]);

        $post = ForumPost::create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $topic->load('category', 'user');
        $topic->loadCount('posts');

        return response()->json($topic, 201);
    }

    /** Récupère un topic avec ses informations détaillées et ses posts */
    public function show(int $id, Request $request): JsonResponse
    {
        $userId = $this->getOptionalUserId($request);

        $topic = ForumTopic::with([
            'category',
            'user',
            'posts' => function ($q) {
                $q->with('user', 'votes')->orderBy('created_at', 'asc');
            },
        ])->withCount('posts')->findOrFail($id);

        $this->loadUserVotes($topic->posts, $userId);

        return response()->json($topic);
    }

    /** Liste paginée des posts pour un topic donné */
    public function posts(int $id, Request $request): JsonResponse
    {
        $perPage = (int) ($request->query('per_page', 10));
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $page = (int) ($request->query('page', 1));
        $page = $page > 0 ? $page : 1;
        $userId = $this->getOptionalUserId($request);

        $topic = ForumTopic::findOrFail($id);

        $paginator = $topic->posts()
            ->with('user', 'votes')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $posts = collect($paginator->items());
        $this->loadUserVotes($posts, $userId);

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

    /** Ajoute un message à un topic */
    public function storePost(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $topic = ForumTopic::findOrFail($id);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $post = ForumPost::create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $post->load('user', 'votes');

        return response()->json($post, 201);
    }

    /** Vote (up/down) sur un message */
    public function vote(int $id, Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $post = ForumPost::findOrFail($id);

        $validated = $request->validate([
            'vote' => ['required', 'in:up,down'],
        ]);

        $existing = ForumPostVote::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->vote === $validated['vote']) {
                $existing->delete();
                $post->load('votes');

                return response()->json(['vote_balance' => $post->vote_balance, 'user_vote' => null]);
            }
            $existing->update(['vote' => $validated['vote']]);
            $post->load('votes');

            return response()->json(['vote_balance' => $post->vote_balance, 'user_vote' => $validated['vote']]);
        }

        ForumPostVote::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'vote' => $validated['vote'],
        ]);

        $post->load('votes');

        return response()->json(['vote_balance' => $post->vote_balance, 'user_vote' => $validated['vote']], 201);
    }
}
