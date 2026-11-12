<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Cleans rich text from the blog editor with an allow-list (HTML Purifier).
 * Only simple formatting survives: no scripts, styles, event handlers, iframes, images or
 * javascript: links, so a post can never run code on the public site or the admin panel.
 */
class HtmlSanitizer
{
    public const ALLOWED = 'p,br,h2,h3,h4,strong,b,em,i,u,s,blockquote,ul,ol,li,a[href|title],hr,code,pre';

    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Browsers' contenteditable uses <div> for new lines and <h1> is reserved for the post title.
        $html = preg_replace(['~<div(\s[^>]*)?>~i', '~</div\s*>~i', '~<(/?)h1(\s[^>]*)?>~i'], ['<p>', '</p>', '<$1h2>'], $html);

        $clean = self::purifier()->purify($html);
        // Drop paragraphs that only hold whitespace / line breaks.
        $clean = preg_replace('~<p>(\s|&nbsp;|<br\s*/?>)*</p>~i', '', $clean);

        return trim($clean);
    }

    /** True when the cleaned HTML has no readable text. */
    public static function isEmpty(?string $html): bool
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\u{A0}") === '';
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('HTML.TargetNoopener', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);

        $cache = storage_path('framework/cache/htmlpurifier');
        if (is_dir($cache) || @mkdir($cache, 0755, true)) {
            $config->set('Cache.SerializerPath', $cache);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
