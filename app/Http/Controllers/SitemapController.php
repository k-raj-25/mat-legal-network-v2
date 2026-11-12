<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [route('home'), route('lawyers.index'), route('blog.index'), route('about'), route('for-lawyers'), route('contact')];
            foreach (array_keys(PageController::LEGAL) as $p) {
                $urls[] = route('legal', $p);
            }
            DB::table('practice_areas')->where('is_active', true)->orderBy('id')->pluck('slug')
                ->each(function ($s) use (&$urls) {
                    $urls[] = route('lawyers.index', ['area' => $s]);
                });
            // Blog: categories and the newest published posts (4k + 45k profiles stays under the 50k-URL limit).
            DB::table('blog_categories')->where('is_active', true)->orderBy('id')->pluck('slug')
                ->each(function ($s) use (&$urls) {
                    $urls[] = route('blog.category', $s);
                });
            DB::table('posts')->where('status', 'published')->whereNotNull('published_at')->orderByDesc('published_at')->limit(4000)->pluck('slug')
                ->each(function ($s) use (&$urls) {
                    $urls[] = route('blog.show', $s);
                });
            // Profiles (capped at the 50k-URL sitemap limit).
            DB::table('lawyer_profiles')->where('status', 'approved')->orderBy('id')->limit(45000)->pluck('slug')
                ->each(function ($s) use (&$urls) {
                    $urls[] = route('lawyers.show', $s);
                });

            $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($urls as $u) {
                $body .= '<url><loc>'.e($u).'</loc></url>';
            }

            return $body.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
