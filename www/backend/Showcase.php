<?php
    // Content for views/whats-new.pvue. The snippets live here (not in the .pvue file) so the
    // compiler never treats them as real directives: they're only printed as text.
    class Showcase {
        public static function features(): array {
            return [
                'pif' => [
                    'title'  => 'p-if works on any element',
                    'before' => '0.0.2 needed the opening tag alone on its line. A one-liner deleted the content and left the if open.',
                    'code'   => <<<'PVUE'
<span p-if="$bOnline" class="text-emerald-600">● Online</span><span p-if="!$bOnline">○ Offline</span>
<input p-if="$bOnline" value="void elements too" readonly>
PVUE,
                ],
                'pfor' => [
                    'title'  => 'p-for over any iterable',
                    'before' => '0.0.2 wrapped the source in isset(), so range() was fatal and generators were skipped.',
                    'code'   => <<<'PVUE'
<li p-for="$n in range(1, 5)">{{ $n }}</li>

<?php $countdown = (function () { yield 3; yield 2; yield 1; })(); ?>
<li p-for="$n in $countdown">{{ $n }}…</li>
PVUE,
                ],
                'pforif' => [
                    'title'  => 'p-for + p-if on one element',
                    'before' => '0.0.2 broke the markup when both directives shared a tag.',
                    'code'   => <<<'PVUE'
<li p-for="$n in $nums" p-if="$n % 2 === 0">{{ $n }}</li>
PVUE,
                ],
                'nested' => [
                    'title'  => 'Nested loops with the same tag',
                    'before' => '0.0.2 ended the loop at the first closing tag with the same name.',
                    'code'   => <<<'PVUE'
<ul>
    <li p-for="$group => $items in $groups">
        <strong>{{ $group }}</strong>
        <ul>
            <li p-for="$item in $items">{{ $item }}</li>
        </ul>
    </li>
</ul>
PVUE,
                ],
                'component' => [
                    'title'  => 'p-for and p-if on component tags',
                    'before' => 'New in 0.0.3. The component reads the loop variable, so there are still no props.',
                    'code'   => <<<'PVUE'
@require StatPill 'components/cards/StatPill.pvue';

<StatPill p-for="$stat in $stats" />
<?php $stat = $total; ?><StatPill p-if="$bOnline" />
PVUE,
                ],
                'mustache' => [
                    'title'  => '{{ }} accepts calls and static methods',
                    'before' => 'In 0.0.2 only $variables converted. {{ count($x) }} was printed as text.',
                    'code'   => <<<'PVUE'
{{ count($groups) }}
{{ number_format(1234567) }}
{{ strtoupper(Lang::code()) }}
{{ $bOnline ? 'yes' : 'no' }}
PVUE,
                ],
                'template' => [
                    'title'  => 'Nested <template> elements',
                    'before' => 'The 0.0.2 template block ended at the first closing template tag.',
                    'code'   => <<<'PVUE'
<template id="toast-tpl">
    <div class="toast">Cloned from a nested template</div>
</template>

<button onclick="cloneToast()">Clone it</button>
PVUE,
                ],
                'ajax' => [
                    'title'  => '@AJAX methods are enforced',
                    'before' => '0.0.2 ignored the method and let ?action= call any PHP function, built-ins included.',
                    'code'   => <<<'PVUE'
@AJAX("POST")
function whatsNewPing(array $input = []): void {
    echo json_encode(['pong' => true]);
}

function whatsNewHidden() { /* not @AJAX: can't be called */ }
PVUE,
                ],
                'cscript' => [
                    'title'  => 'Array values in <cscript>',
                    'before' => '0.0.2 only injected simple variable names into client JS.',
                    'code'   => <<<'PVUE'
const stats = {{ $nested['stats'] }};
const first = {{ $stats[0] }};
PVUE,
                ],
                'lang' => [
                    'title'  => '<html lang> per request',
                    'before' => '0.0.2 could only set the language statically per view.',
                    'code'   => <<<'PVUE'
// App.pvue <script> (runs before <html> is printed)
phpue_set_lang($_GET['lang'] ?? 'en');

// priority: phpue_set_lang() > <meta name="lang"> > PHPUE_LANG
PVUE,
                ],
            ];
        }

        /** Fixes without a visual demo. */
        public static function alsoFixed(): array {
            return [
                ['title' => 'Lazy sessions',          'text' => "PHPUE_SESSION = 'lazy' keeps anonymous pages cacheable. phpue_session(true) starts one when you need it."],
                ['title' => 'Dev tools stay local',   'text' => '?compile=, ?hot-reload and web ?build only run in source mode, never on a built site.'],
                ['title' => 'Comment-proof blocks',   'text' => 'Block tags inside comments and strings no longer hijack <script>, <header> or <template>.'],
                ['title' => 'Richer App header',      'text' => 'The App header prints {{ $arr[\'key\'] }} and {{ $obj->prop }} as well as plain variables.'],
                ['title' => 'Steadier builds',        'text' => 'Builds work without components/, and @AJAX handlers in nested components are collected.'],
                ['title' => 'Predictable backend/',   'text' => 'Files load in sorted order on every OS, and loader variables no longer clobber yours.'],
                ['title' => 'Recursive hot reload',   'text' => 'Saving anything under views/ or components/ (any depth) reloads the page.'],
                ['title' => 'PHP 8.0+',               'text' => 'The new compiler uses str_contains(), so 0.0.3 needs PHP 8.0 or newer.'],
            ];
        }
    }
?>
