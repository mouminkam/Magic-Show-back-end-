<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Models\BlogPageSetting;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    /**
     * Format date for frontend (e.g. FEBRUARY 3, 2016).
     */
    private static function formatDate($date): string
    {
        if (!$date) {
            return '';
        }
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);
        return strtoupper($d->format('F j, Y'));
    }

    /**
     * Transform a BlogPost for list/single response (id, title, excerpt, image, date).
     * @param int|null $cacheBust If set, appends ?v= to image URL for cache-busting.
     */
    private static function transformPost($post, ?int $cacheBust = null): array
    {
        $img = $post->featured_image_url ?? null;
        if ($img && $cacheBust) {
            $img .= (str_contains($img, '?') ? '&' : '?') . 'v=' . $cacheBust;
        }
        return [
            'id' => $post->id,
            'title' => LocaleHelper::transAttr($post, 'title') ?? $post->title,
            'excerpt' => LocaleHelper::transAttr($post, 'excerpt') ?? $post->excerpt ?? '',
            'image' => $img,
            'date' => self::formatDate($post->published_at),
            'content' => LocaleHelper::transAttr($post, 'content') ?? $post->content ?? null,
            'allow_comments' => (bool) ($post->allow_comments ?? true),
        ];
    }

    /**
     * Transform a Comment for frontend (id, author, date, content, replies).
     */
    private static function transformComment(Comment $comment): array
    {
        $item = [
            'id' => $comment->id,
            'author' => $comment->author_name,
            'date' => self::formatDate($comment->created_at),
            'content' => $comment->comment,
            'replies' => [],
        ];
        if ($comment->relationLoaded('replies') && $comment->replies->isNotEmpty()) {
            $item['replies'] = $comment->replies->map(fn ($r) => self::transformComment($r))->values()->all();
        }
        return $item;
    }

    /**
     * Get blog banner data (from dashboard settings).
     * Uses Cache::forget on invalidate - no version key (fixes immediate update after dashboard edit).
     */
    public function banner()
    {
        $locale = app()->getLocale();
        $cacheKey = "blog_banner_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $setting = BlogPageSetting::get();
            $imgUrl = $setting->hero_background_image_url ?? asset('images/blog-banner.jpg');
            $ts = $setting->updated_at?->timestamp ?? time();
            return [
                'title' => LocaleHelper::transAttr($setting, 'hero_title') ?? __('blog.banner.title'),
                'subtitle' => LocaleHelper::transAttr($setting, 'hero_subtitle') ?? __('blog.banner.subtitle'),
                'backgroundImage' => $imgUrl . (str_contains($imgUrl, '?') ? '&' : '?') . 'v=' . $ts,
                'leftBadge' => LocaleHelper::transAttr($setting, 'hero_left_badge'),
                'rightBadge' => LocaleHelper::transAttr($setting, 'hero_right_badge'),
            ];
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get blog posts with pagination. Returns items as id, title, excerpt, image, date.
     */
    public function posts(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = min(max((int) $request->get('limit', 3), 1), 20);
        $locale = app()->getLocale();
        $cacheKey = "blog_posts_{$page}_{$limit}_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($limit, $page) {
            $posts = BlogPost::with('author')
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate($limit, ['*'], 'page', $page);

            $transformed = $posts->getCollection()->map(function ($post) {
                $ts = $post->updated_at?->timestamp ?? time();
                return self::transformPost($post, $ts);
            })->values()->all();
            return [
                'items' => $transformed,
                'pagination' => [
                    'currentPage' => $posts->currentPage(),
                    'limit' => $posts->perPage(),
                    'totalItems' => $posts->total(),
                    'totalPages' => $posts->lastPage(),
                    'hasNext' => $posts->hasMorePages(),
                    'hasPrev' => $posts->currentPage() > 1,
                ],
            ];
        });

        return ApiResponseHelper::success(
            ['items' => $data['items']],
            null,
            ['pagination' => $data['pagination']]
        );
    }

    /**
     * Get single blog post. Returns id, title, content, image, date.
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        $cacheKey = "blog_post_{$id}_{$locale}";

        $payload = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($id) {
            $post = BlogPost::with('author')
                ->where('status', 'published')
                ->find($id);

            if (!$post) {
                return null;
            }

            $ts = $post->updated_at?->timestamp ?? time();
            $payload = self::transformPost($post, $ts);
            $payload['content'] = LocaleHelper::transAttr($post, 'content') ?? $post->content;
            return $payload;
        });

        if (!$payload) {
            abort(404, __('errors.not_found'));
        }

        $post = BlogPost::find($id);
        if ($post) {
            $post->incrementViewCount();
        }

        return ApiResponseHelper::success($payload);
    }

    /**
     * Get blog post comments. Returns id, author, date, content, replies (same shape).
     * Uses TTL_VOLATILE - invalidated by storeComment().
     */
    public function comments($id)
    {
        $locale = app()->getLocale();
        $cacheKey = "blog_post_comments_{$id}_{$locale}";

        $transformed = Cache::remember($cacheKey, CacheService::TTL_VOLATILE, function () use ($id) {
            $comments = Comment::where('blog_post_id', $id)
                ->where('is_approved', true)
                ->whereNull('parent_id')
                ->with('replies')
                ->latest()
                ->get();

            return $comments->map(fn ($c) => self::transformComment($c))->values()->all();
        });

        return ApiResponseHelper::success($transformed);
    }

    /**
     * Store a new comment.
     */
    public function storeComment(Request $request, $id)
    {
        $post = BlogPost::where('status', 'published')->find($id);
        if (!$post) {
            return ApiResponseHelper::notFound(__('errors.not_found'));
        }
        if (!$post->allow_comments) {
            return ApiResponseHelper::forbidden(__('errors.comments_closed'));
        }

        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email',
            'comment' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);
            if (!$parent || (int) $parent->blog_post_id !== (int) $id) {
                return ApiResponseHelper::error(
                    'INVALID_PARENT',
                    __('errors.validation_failed'),
                    422
                );
            }
        }

        $comment = Comment::create([
            'blog_post_id' => (int) $id,
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'comment' => $validated['comment'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_approved' => false
        ]);

        CacheService::invalidateBlogPostComments((int) $id);

        return ApiResponseHelper::success(self::transformComment($comment), __('messages.comment_submitted'));
    }
}
