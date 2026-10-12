<?php
    // backend/ is autoloaded, but a folder with an autoload.php only loads THIS file.
    // That keeps the language packs (en.php, cy.php) from running on every request.
    //
    // Usage anywhere (App, views, components, @AJAX handlers):
    //     $lang = Lang::pack();               // ['home_heroTitle' => '...', ...]
    //     {{ Lang::t('nav_home') }}           // 0.0.3: {{ }} accepts static calls
    class Lang {
        public const SUPPORTED = ['en' => 'English', 'cy' => 'Cymraeg'];
        public const FALLBACK  = 'en';
        private const COOKIE   = 'phpue_lang';

        private static ?string $code = null;
        private static array $packs = [];

        /** Resolve the language once per request: ?lang= > cookie > fallback. */
        public static function code(): string {
            if (self::$code !== null) return self::$code;

            $requested = $_GET['lang'] ?? $_COOKIE[self::COOKIE] ?? self::FALLBACK;
            self::$code = isset(self::SUPPORTED[$requested]) ? $requested : self::FALLBACK;
            return self::$code;
        }

        /** Called from App.pvue: remembers a ?lang= choice and sets <html lang> (0.0.3). */
        public static function boot(): void {
            $code = self::code();

            if (isset($_GET['lang']) && ($_COOKIE[self::COOKIE] ?? null) !== $code && !headers_sent()) {
                setcookie(self::COOKIE, $code, ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'samesite' => 'Lax']);
            }

            phpue_set_lang($code);
        }

        public static function pack(?string $code = null): array {
            $code = $code ?? self::code();
            if (!isset(self::$packs[$code])) {
                $file = __DIR__ . '/' . $code . '.php';
                self::$packs[$code] = file_exists($file) ? (require $file) : [];
            }

            // Missing keys fall back to English, so a half-translated pack still renders.
            return $code === self::FALLBACK ? self::$packs[$code] : self::$packs[$code] + self::pack(self::FALLBACK);
        }

        public static function t(string $key): string {
            return self::pack()[$key] ?? $key;
        }

        /** Language switcher links that keep the current path. */
        public static function links(): array {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $links = [];
            foreach (self::SUPPORTED as $code => $label) {
                $links[] = ['code' => $code, 'label' => $label, 'url' => $path . '?lang=' . $code, 'bActive' => $code === self::code()];
            }
            return $links;
        }
    }
?>
