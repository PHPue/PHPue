<?php
    // Content for views/whats-new.pvue. The snippets live here (not in the .pvue file) so the
    // compiler never treats them as real directives: they're only printed as text.
    //
    // Titles and descriptions come from the language packs (sc_<id>_title, sc_<id>_before,
    // fix_<n>_title, fix_<n>_text). Text inside a snippet uses %tokens% that are swapped for
    // the same pack strings the live demo renders, so the source always matches the result.
    class Showcase {
        private const CODE = [
            'pif' => <<<'PVUE'
<span p-if="$bOnline">%demo_online%</span><span p-if="!$bOnline">%demo_offline%</span>
<input p-if="$bOnline" value="%demo_void%" readonly>
PVUE,
            'pfor' => <<<'PVUE'
<li p-for="$n in range(1, 5)">{{ $n }}</li>

<?php $countdown = (function () { yield 3; yield 2; yield 1; })(); ?>
<li p-for="$n in $countdown">{{ $n }}…</li>
PVUE,
            'pforif' => <<<'PVUE'
<li p-for="$n in $nums" p-if="$n % 2 === 0">{{ $n }}</li>
PVUE,
            'nested' => <<<'PVUE'
<ul>
    <li p-for="$group => $items in $groups">
        <strong>{{ $group }}</strong>
        <ul>
            <li p-for="$item in $items">{{ $item }}</li>
        </ul>
    </li>
</ul>
PVUE,
            'component' => <<<'PVUE'
@require StatPill 'components/cards/StatPill.pvue';

<StatPill p-for="$stat in $stats" />
<?php $stat = $total; ?><StatPill p-if="$bOnline" />
PVUE,
            'mustache' => <<<'PVUE'
{{ count($groups) }}
{{ number_format(1234567) }}
{{ strtoupper(Lang::code()) }}
{{ $bOnline ? '%demo_yes%' : '%demo_no%' }}
PVUE,
            'template' => <<<'PVUE'
<template id="toast-tpl">
    <div class="toast">%demo_toast%</div>
</template>

<button onclick="cloneToast()">%new_clone%</button>
PVUE,
            'ajax' => <<<'PVUE'
@AJAX("POST")
function whatsNewPing(array $input = []): void {
    echo json_encode(['pong' => true]);
}

function whatsNewHidden() { /* %demo_notAjax% */ }
PVUE,
            'cscript' => <<<'PVUE'
const stats = {{ $nested['stats'] }};
const first = {{ $stats[0] }};
PVUE,
            'lang' => <<<'PVUE'
// App.pvue <script> (%demo_langRuns%)
phpue_set_lang($_GET['lang'] ?? 'en');

// phpue_set_lang() > <meta name="lang"> > PHPUE_LANG
PVUE,
        ];

        private const ALSO_FIXED = 8;

        public static function features(): array {
            $pack = Lang::pack();
            $tokens = [];
            foreach ($pack as $key => $value) {
                $tokens['%' . $key . '%'] = $value;
            }

            $features = [];
            foreach (self::CODE as $id => $code) {
                $features[$id] = [
                    'title'  => $pack['sc_' . $id . '_title'],
                    'before' => $pack['sc_' . $id . '_before'],
                    'code'   => strtr($code, $tokens),
                ];
            }
            return $features;
        }

        /** Fixes without a visual demo. */
        public static function alsoFixed(): array {
            $fixes = [];
            for ($n = 1; $n <= self::ALSO_FIXED; $n++) {
                $fixes[] = ['title' => Lang::t("fix_{$n}_title"), 'text' => Lang::t("fix_{$n}_text")];
            }
            return $fixes;
        }
    }
?>
