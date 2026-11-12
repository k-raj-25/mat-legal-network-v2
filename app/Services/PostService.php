<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\AuditLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostService
{
    /**
     * Create or update a post. $action: save (keep status; new posts are drafts), publish, unpublish.
     * Body must already be sanitised (PostRequest does it).
     */
    public function save(Post $post, array $data, ?UploadedFile $image, User $actor): Post
    {
        $creating = ! $post->exists;
        $action = $data['action'] ?? 'save';
        $wasPublished = $post->isPublished();
        $oldImage = $post->featured_image_path;
        $newImage = null;

        $post->fill([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? '',
            'blog_category_id' => $data['blog_category_id'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ]);
        $post->slug = Post::uniqueSlug($data['slug'] ?: $data['title'], $post->id);
        if ($creating) {
            $post->user_id = $actor->id;
        }

        if ($image) {
            $newImage = $image->store('blog', 'public');
            $post->featured_image_path = $newImage;
        } elseif (! empty($data['remove_image'])) {
            $post->featured_image_path = null;
        }
        $post->featured_image_alt = $post->featured_image_path ? trim((string) ($data['featured_image_alt'] ?? '')) : null;

        if ($action === 'publish') {
            $post->status = PostStatus::Published;
            $post->published_at ??= now(); // keep the original date when re-publishing
        } elseif ($action === 'unpublish') {
            $post->status = PostStatus::Draft;
        }

        try {
            DB::transaction(function () use ($post, $creating, $wasPublished, $oldImage) {
                $post->save();

                $meta = ['title' => $post->title];
                AuditLog::record($creating ? 'post.created' : 'post.updated', $post, $meta);
                if (! $wasPublished && $post->isPublished()) {
                    AuditLog::record('post.published', $post, $meta);
                } elseif ($wasPublished && ! $post->isPublished()) {
                    AuditLog::record('post.unpublished', $post, $meta);
                }

                if ($oldImage && $oldImage !== $post->featured_image_path) {
                    DB::afterCommit(fn () => Storage::disk('public')->delete($oldImage));
                }
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $e;
        }

        return $post;
    }

    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post) {
            AuditLog::record('post.deleted', $post, ['title' => $post->title]);
            $post->delete(); // the model removes the featured image
        });
    }
}
