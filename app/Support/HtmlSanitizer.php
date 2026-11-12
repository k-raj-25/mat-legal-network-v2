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

    /** Tests set this to exercise the built-in fallback. */
    public static bool $forceFallback = false;

    /** Elements dropped together with everything inside them. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button', 'head', 'title', 'meta', 'link', 'base', 'frame', 'frameset', 'applet', 'audio', 'video', 'canvas', 'img', 'picture', 'source', 'input'];

    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Browsers' contenteditable uses <div> for new lines and <h1> is reserved for the post title.
        $html = preg_replace(['~<div(\s[^>]*)?>~i', '~</div\s*>~i', '~<(/?)h1(\s[^>]*)?>~i'], ['<p>', '</p>', '<$1h2>'], $html);

        $clean = self::purifierAvailable() ? self::purifier()->purify($html) : self::cleanWithDom($html);
        // Drop paragraphs that only hold whitespace / line breaks.
        $clean = preg_replace('~<p>(\s|&nbsp;|<br\s*/?>)*</p>~i', '', $clean);

        return trim($clean);
    }

    /** True when the cleaned HTML has no readable text. */
    public static function isEmpty(?string $html): bool
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\u{A0}") === '';
    }

    private static function purifierAvailable(): bool
    {
        static $warned = false;
        if (! self::$forceFallback && class_exists(HTMLPurifier::class)) {
            return true;
        }
        if (! self::$forceFallback && ! $warned) {
            $warned = true;
            logger()->warning('HTML Purifier is not installed (run "composer install"). Blog posts are cleaned with the built-in fallback sanitizer.');
        }

        return false;
    }

    /**
     * Built-in allow-list sanitizer, used only if the HTML Purifier package is missing (e.g. vendor/ not
     * updated after pulling). Same rules: allowed tags only, no attributes except a safe href/title on links.
     */
    public static function cleanWithDom(string $html): string
    {
        $allowed = [];
        foreach (explode(',', self::ALLOWED) as $tag) {
            $allowed[strtok($tag, '[')] = true;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="__root">'.$html.'</div></body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return '';
        }
        self::walk($root, $allowed);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(\DOMNode $parent, array $allowed): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMText) {
                continue;
            }
            if (! $node instanceof \DOMElement) {
                $parent->removeChild($node); // comments, processing instructions, CDATA

                continue;
            }
            $tag = strtolower($node->tagName);
            if (in_array($tag, self::DROP, true)) {
                $parent->removeChild($node);

                continue;
            }
            self::walk($node, $allowed);
            if (! isset($allowed[$tag])) {
                // Unknown element: keep its (already cleaned) content, drop the tag.
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }
            $href = $tag === 'a' ? trim((string) $node->getAttribute('href')) : '';
            $title = $tag === 'a' ? (string) $node->getAttribute('title') : '';
            foreach (iterator_to_array($node->attributes) as $attr) {
                $node->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if ($href !== '' && self::safeUrl($href)) {
                    $node->setAttribute('href', $href);
                }
                if ($title !== '') {
                    $node->setAttribute('title', mb_substr($title, 0, 200));
                }
            }
        }
    }

    private static function safeUrl(string $url): bool
    {
        // Strip control characters and whitespace browsers ignore inside schemes (e.g. "java\tscript:").
        $probe = strtolower(preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5)));
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/', $probe, $m)) {
            return in_array($m[1], self::SAFE_SCHEMES, true);
        }

        return true; // relative links (/blog/..., #section) have no scheme
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
