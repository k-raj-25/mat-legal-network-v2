{{--
    Rich text editor. Without JavaScript it is a plain textarea of HTML.
    Whatever is sent, the server keeps only safe formatting (App\Support\HtmlSanitizer).
--}}
@php
    $tools = [
        ['block', 'p', 'Paragraph', 'P'],
        ['block', 'h2', 'Heading', 'H2'],
        ['block', 'h3', 'Subheading', 'H3'],
        ['cmd', 'bold', 'Bold (Ctrl+B)', null, 'font-extrabold', 'B'],
        ['cmd', 'italic', 'Italic (Ctrl+I)', null, 'italic font-serif', 'I'],
        ['cmd', 'insertUnorderedList', 'Bulleted list', 'list'],
        ['cmd', 'insertOrderedList', 'Numbered list', 'list-number'],
        ['block', 'blockquote', 'Quote', 'quote'],
        ['link', 'link', 'Add link', 'link'],
        ['cmd', 'unlink', 'Remove link', 'x'],
    ];
@endphp
<div x-data="richText" data-testid="rich-editor">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <label for="f_body" id="f_body_label" class="form-label" @click.prevent="focus()">Article <span class="text-rose-500" aria-hidden="true">*</span></label>
        <p class="mb-1.5 text-xs text-slate-500" x-show="ready" x-cloak aria-live="polite"><span x-text="words"></span> words · <span x-text="Math.max(1, Math.ceil(words / 200))"></span> min read</p>
    </div>
    <div role="toolbar" aria-label="Formatting" aria-controls="f_body_editor" class="flex flex-wrap gap-1 rounded-t-xl border-2 border-b-0 border-slate-200 bg-slate-50 p-1.5" x-show="ready" x-cloak @keydown="toolbarKeys($event)">
        @foreach ($tools as $i => $t)
            <button type="button" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-bold text-ink transition hover:bg-white aria-pressed:bg-ink aria-pressed:text-white"
                    tabindex="{{ $i === 0 ? 0 : -1 }}" data-tool
                    aria-label="{{ $t[2] }}" title="{{ $t[2] }}"
                    @if ($t[0] !== 'link' && $t[1] !== 'unlink') :aria-pressed="String(active['{{ $t[1] }}'] === true)" @endif
                    @mousedown.prevent @click="run('{{ $t[0] }}', '{{ $t[1] }}')">
                @if (! empty($t[5]))<span class="{{ $t[4] }}" aria-hidden="true">{{ $t[5] }}</span>
                @elseif (strlen($t[3]) <= 2)<span aria-hidden="true">{{ $t[3] }}</span>
                @else<x-icon :name="$t[3]" class="size-4"/>@endif
            </button>
            @if (in_array($i, [2, 4, 7]))<span class="mx-0.5 my-1.5 w-px bg-slate-200" aria-hidden="true"></span>@endif
        @endforeach
    </div>
    <div x-ref="editor" id="f_body_editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="f_body_label" aria-describedby="f_body_help"
         aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}"
         class="prose-blog min-h-[22rem] rounded-b-xl border-2 border-slate-200 bg-white px-4 py-3 text-ink focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 focus:outline-none aria-[invalid=true]:border-rose-400 sm:px-6"
         x-show="ready" x-cloak data-testid="body-editor"
         @input="sync()" @blur="sync()" @paste="paste($event)" @keyup="refresh()" @mouseup="refresh()"></div>
    <textarea id="f_body" name="body" x-ref="field" rows="16" class="form-input font-mono text-xs" :hidden="ready" aria-describedby="f_body_help">{{ $value }}</textarea>
    <p id="f_body_help" class="form-help">Use Heading and Subheading to break up long articles. Pasted text arrives without its formatting. Only safe formatting is kept: links, lists, quotes, bold and italic.</p>
    @error('body')<p class="form-error">{{ $message }}</p>@enderror
</div>
