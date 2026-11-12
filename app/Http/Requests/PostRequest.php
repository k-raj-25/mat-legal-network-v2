<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Create / update a blog post. Who may do it is checked by PostPolicy in the controller. */
class PostRequest extends FormRequest
{
    public const IMAGE_MAX_KB = 4096;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'slug' => $this->filled('slug') ? strtolower(trim((string) $this->input('slug'))) : null,
            'body' => HtmlSanitizer::clean($this->input('body')),
            'action' => $this->input('action', 'save'),
        ]);
    }

    public function rules(): array
    {
        /** @var Post|null $post */
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'min:5', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'blog_category_id' => ['nullable', 'integer', Rule::exists('blog_categories', 'id')->where(fn ($q) => $q->where('is_active', true)->orWhere('id', (int) $post?->blog_category_id))],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:200000'],
            'featured_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::IMAGE_MAX_KB, 'dimensions:max_width=6000,max_height=6000'],
            'featured_image_alt' => ['nullable', 'string', 'max:160'],
            'remove_image' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'action' => ['required', Rule::in(['save', 'publish', 'unpublish'])],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Use only lowercase letters, numbers and single hyphens, e.g. cheque-bounce-explained.',
            'slug.unique' => 'Another post already uses this web address.',
            'featured_image.max' => 'The featured image must be 4 MB or smaller.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $post = $this->route('post');
            $publishing = $this->input('action') === 'publish' || ($post?->isPublished() && $this->input('action') === 'save');
            if ($publishing && HtmlSanitizer::isEmpty($this->input('body'))) {
                $v->errors()->add('body', 'Write the article before publishing it.');
            }
            $hasImage = $this->hasFile('featured_image') || ($post?->featured_image_path && ! $this->boolean('remove_image'));
            if ($hasImage && trim((string) $this->input('featured_image_alt')) === '') {
                $v->errors()->add('featured_image_alt', 'Describe the featured image for people who use screen readers.');
            }
        }];
    }
}
