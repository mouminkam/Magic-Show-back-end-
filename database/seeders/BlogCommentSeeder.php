<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Database\Seeder;

class BlogCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = BlogPost::where('status', 'published')->limit(4)->get();
        if ($posts->isEmpty()) {
            return;
        }

        $commentsData = [
            ['author_name' => 'سارة أحمد', 'author_email' => 'sara@example.com', 'comment' => 'مقال رائع! استفدت كثيراً من النصائح. شكراً لكم.'],
            ['author_name' => 'محمد خالد', 'author_email' => 'mohamed@example.com', 'comment' => 'أحببت الطريقة التي قدمتم فيها المحتوى. ننتظر المزيد.'],
            ['author_name' => 'ليلى حسن', 'author_email' => 'laila@example.com', 'comment' => 'نصائح عملية جداً. جربتها وكانت النتيجة ممتازة.'],
            ['author_name' => 'أحمد علي', 'author_email' => 'ahmed@example.com', 'comment' => 'محتوى مميز ومفيد. استمرروا على هذا المستوى.'],
            ['author_name' => 'فاطمة عمر', 'author_email' => 'fatima@example.com', 'comment' => 'أول مرة أقرأ مقالاً بهذه الجودة عن الموضة.'],
            ['author_name' => 'يوسف كريم', 'author_email' => 'youssef@example.com', 'comment' => 'التفاصيل دقيقة والصور جميلة. شكراً للمجهود.'],
        ];

        foreach ($posts as $index => $post) {
            $count = $index === 0 ? 3 : ($index === 1 ? 2 : 1);
            $used = [];
            for ($i = 0; $i < $count; $i++) {
                $idx = array_rand($commentsData);
                while (in_array($idx, $used)) {
                    $idx = array_rand($commentsData);
                }
                $used[] = $idx;
                $c = $commentsData[$idx];
                Comment::create([
                    'blog_post_id' => $post->id,
                    'author_name' => $c['author_name'],
                    'author_email' => $c['author_email'],
                    'comment' => $c['comment'],
                    'is_approved' => true,
                    'parent_id' => null,
                ]);
            }
        }

        foreach (BlogPost::all() as $post) {
            $post->update(['comment_count' => Comment::where('blog_post_id', $post->id)->count()]);
        }
    }
}
