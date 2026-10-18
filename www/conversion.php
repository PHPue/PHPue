<?php
    /*  ================ VERSION 0.0.3 (bug-fix release) ================
        - Blocks (<script>, <header>, <template>, <cscript>) are located at the TOP LEVEL of the file:
            literal block tags inside comments/strings no longer hijack blocks, a bare <header> element
            inside <template> is kept, and nested <template> tags (e.g. Alpine x-for / x-if) work.
        - p-if / p-for are tag-aware (balanced tags, quote/PHP/comment aware) and run BEFORE {{ }}:
            single-line elements, same-tag children, '>' or {{ }} in attributes, p-for + p-if on one
            element, function-call sources and Traversables all work.
        - {{ }} also accepts function / static calls ({{ count($items) }}); <cscript> {{ }} accepts any
            $expression ({{ $data['items'] }}); App <header> accepts {{ $meta['title'] }} / {{ $obj->tag }}.
        - @AJAX: double quotes and return types accepted; the declared method is enforced and ONLY
            @AJAX functions can be called through ?action= / {"action": ...}. Component @AJAX handlers
            are kept by the build.
        - Components: <Name />, <Name/>, <Name></Name>; @require accepts double quotes.
        - Runtime <html lang> (phpue_set_lang() > view <meta name="lang"> > PHPUE_LANG).
        - Session policy: PHPUE_SESSION 'auto' (0.0.2 behaviour) or 'lazy' + phpue_session().
        - Backend files load in sorted (deterministic) order.
    */
    class PHPRouting {
        public $routes = [];

        public function addView($viewPath) {
            $viewName = basename($viewPath, '.pvue');
            $headerContent = $this->extractHeaderContent($viewPath);

            $this->routes[$viewName] = [
                'file' => $viewPath,
                'compiled' => '.dist/pages/' . $viewName . '.php',
                'route' => $viewName === 'index' ? '/' : "/$viewName",
                'header' => $this->extractMetaData($headerContent)
            ];
        }

        public function addCompiledView($phpFilePath) {
            $viewName = basename($phpFilePath, '.php');

            $headerContent = $this->extractHeaderFromCompiled($phpFilePath);

            $this->routes[$viewName] = [
                'file' => $phpFilePath,
                'compiled' => $phpFilePath,
                'route' => $viewName === 'index' ? '/' : "/$viewName",
                'header' => $this->extractMetaData($headerContent)
            ];
        }

        private function extractHeaderFromCompiled($phpFile) {
            if (!file_exists($phpFile)) return '';

            $content = file_get_contents($phpFile);

            /*  ================ VERSION 0.0.3 ================
                - Match the exact heredoc terminator: the old pattern stopped at the first "HTML"
                  text inside the header, losing meta in compiled mode. */
            if (preg_match('/\$phpue_header = <<<HTML\n(.*?)\nHTML;/s', $content, $matches)) {
                return $matches[1] ?? '';
            }
            /*  ================ END | VERSION 0.0.3 | END ================ */

            if (preg_match('/<header>(.*?)<\/header>/s', $content, $matches)) {
                return $matches[1] ?? '';
            }

            if (preg_match('/<head>(.*?)<\/head>/s', $content, $matches)) {
                return $matches[1] ?? '';
            }

            return '';
        }


        private function extractHeaderContent($pvueFile) {
            if (!file_exists($pvueFile)) return '';

            // 0.0.3: same top-level block parser as the compiler (comment/string safe)
            return PHPueConverter::extractBlocks(file_get_contents($pvueFile))['header'];
        }

        private function extractMetaData($headerContent) {
            if (empty($headerContent)) return [];

            $meta = [];

            // 0.0.3: ignore HTML comments when reading the title (a "<title>" in a comment used to win)
            $headerWithoutComments = preg_replace('/<!--.*?-->/s', '', $headerContent);
            if (preg_match('/<title>(.*?)<\/title>/s', $headerWithoutComments, $matches)) {
                $titleContent = trim($matches[1]);
                $meta['title'] = $titleContent;
            }

            /*  ================ VERSION 0.0.2 ================
                - Added HTML LANG (instead of defaulting to 'en')
                - Added Static View Langs (use <header><meta name="lang" content="br"></header>)
                ================ VERSION 0.0.3 ================
                - No implicit 'en': an unset view lang now falls back to PHPUE_LANG at runtime.
            */
            if (preg_match('/<meta\s+name="lang"\s+content="([^"]+)"/', $headerContent, $matches))
                $meta['lang'] = $matches[1];
            elseif (preg_match('/<!--\s*lang:\s*([\w-]+)\s*-->/', $headerContent, $matches))
                $meta['lang'] = $matches[1];
            else
                $meta['lang'] = null;
            /*  ================ END | VERSION 0.0.3 | END ================ */

            $processedHeader = $this->processHeaderTemplates($headerContent);

            $meta['raw_header'] = $headerContent;
            $meta['processed_header'] = $processedHeader;

            return $meta;
        }

        public function processHeaderTemplates($headerContent) {
            /*  ================ VERSION 0.0.3 ================
                - {{ $var }} -> $var (as before), and {{ $arr['k'] }} / {{ $obj->prop }} -> {$arr['k']}
                  so the App <header> heredoc can print array/object values (MetaControl arrays). */
            $headerContent = preg_replace_callback(
                '/\{\{\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)((?:\[(?:\'[^\']*\'|"[^"]*"|\d+|\$\w+)\]|->\w+)*)\s*\}\}/',
                function($matches) {
                    if ($matches[2] === '') {
                        return htmlspecialchars($matches[1]);
                    }
                    return '{' . $matches[1] . $matches[2] . '}';
                },
                $headerContent
            );
            /*  ================ END | VERSION 0.0.3 | END ================ */

            return $headerContent;
        }

        public function getNavigation() {
            $nav = [];
            foreach ($this->routes as $name => $route) {
                $nav[] = [
                    'name' => $name,
                    'title' => $route['header']['title'] ?? ucfirst($name),
                    'url' => $route['route']
                ];
            }
            return $nav;
        }

        public function getRouteMeta($routeName) {
            return $this->routes[$routeName]['header'] ?? [];
        }

        public function preProcessCurrentPage($sourceFile = null) {
            if ($sourceFile === null) {
                $currentRoute = $_GET['page'] ?? 'index';
                $sourceFile = $this->routes[$currentRoute]['file'] ?? 'views/index.pvue';
            }

            if (file_exists($sourceFile)) {
                $content = file_get_contents($sourceFile);
                $converter = get_phpue_converter();

                $converter->preProcessForAjax($content, $sourceFile);

                $GLOBALS['phpue_current_page_code'] = $converter->convertPVueToPHP($content, false, $sourceFile);
                return true;
            }
            return false;
        }

        public function getCurrentPageContent() {
            $currentRoute = $_GET['page'] ?? 'index';

            if (!isset($this->routes[$currentRoute])) {
                http_response_code(404);

                $headerFile = 'httpReqs/http404Head.php';
                if (file_exists($headerFile)) {
                    include $headerFile;
                    if (isset($phpue_header)) {
                        $GLOBALS['phpue_http404_header'] = $phpue_header;
                    }
                }

                $http404File = 'httpReqs/http404.php';
                if (file_exists($http404File)) {
                    ob_start();
                    include $http404File;
                    return ob_get_clean();
                }

                if (isset($this->routes['404'])) {
                    $currentRoute = '404';
                } else {
                    return "<h1 style='text-align: center; font-weight: bold;'>404 - Page Not Found</h1>";
                }
            }

            if (isset($GLOBALS['phpue_current_page_code'])) {
                ob_start();
                eval('?>' . $GLOBALS['phpue_current_page_code']);
                return ob_get_clean();
            }

            if (isset($this->routes[$currentRoute])) {
                $route = $this->routes[$currentRoute];
                $compiledFile = $route['compiled'];

                if (file_exists($compiledFile)) {
                    ob_start();
                    include $compiledFile;
                    return ob_get_clean();
                }
            }

            $compiledIndex = file_exists('pages/index.php') ? 'pages/index.php' : '.dist/pages/index.php';
            if (file_exists($compiledIndex)) {
                ob_start();
                include $compiledIndex;
                return ob_get_clean();
            }

            return "<div>Page not found: $currentRoute</div>";
        }

        public function buildHeaderFromMeta($meta) {
            $header = '';

            if (isset($meta['processed_header']) && !empty($meta['processed_header'])) {
                $header = $meta['processed_header'];
            } elseif (isset($meta['raw_header']) && !empty($meta['raw_header'])) {
                $header = $meta['raw_header'];
            }

            $header = $this->processAssetPaths($header);
            $header = $this->ensureCorrectScriptOrder($header);

            return $header;
        }

        public function executeHeaderPHP($headerContent) {
            ob_start();

            $phpCode = '?>' . $headerContent;

            eval($phpCode);

            $result = ob_get_clean();
            return $result;
        }

        public function ensureCorrectScriptOrder($header) {
            preg_match_all('/<script[^>]*src="[^"]*jquery[^"]*"[^>]*><\/script>/i', $header, $jqueryMatches);
            preg_match_all('/<script[^>]*src="[^"]*bootstrap[^"]*"[^>]*><\/script>/i', $header, $bootstrapMatches);

            $header = preg_replace('/<script[^>]*src="[^"]*jquery[^"]*"[^>]*><\/script>/i', '', $header);
            $header = preg_replace('/<script[^>]*src="[^"]*bootstrap[^"]*"[^>]*><\/script>/i', '', $header);

            $orderedScripts = implode("\n", $jqueryMatches[0]) . "\n" . implode("\n", $bootstrapMatches[0]);

            if (strpos($header, '</head>') !== false) {
                $header = str_replace('</head>', $orderedScripts . "\n</head>", $header);
            } else {
                $header .= $orderedScripts;
            }

            return $header;
        }


        private function processAssetPaths($headerContent) {
            $headerContent = preg_replace_callback(
                '/<script\s+[^>]*src="([^"]*)"[^>]*>/',
                function($matches) {
                    $src = $matches[1];
                    if (strpos($src, 'assets/') === 0 && $src[0] !== '/') {
                        $src = '/' . $src;
                    }
                    return str_replace($matches[1], $src, $matches[0]);
                },
                $headerContent
            );

            $headerContent = preg_replace_callback(
                '/<link\s+[^>]*href="([^"]*)"[^>]*>/',
                function($matches) {
                    $href = $matches[1];
                    if (strpos($href, 'assets/') === 0 && $href[0] !== '/') {
                        $href = '/' . $href;
                    }
                    return str_replace($matches[1], $href, $matches[0]);
                },
                $headerContent
            );

            return $headerContent;
        }
    }


    class PHPueConverter {
        private $routing;
        private $ajaxHandlingCode = [];
        private $ajaxFunctions = [];
        private $currentPageName = '';

        // 0.0.3: elements that never have a closing tag (p-if / p-for wrap just the tag)
        private const VOID_TAGS = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

        public function __construct() {
            $this->routing = new PHPRouting();
            $this->ajaxHandlingCode = [];
            $this->ajaxFunctions = [];
        }

        /*  ================ VERSION 0.0.3 ================
            - Top-level block parser. Only tags at the top level of the .pvue file open a block
              (HTML comments between blocks are skipped), so a literal "<header>" inside the PHP
              script or a comment can no longer become the header block, and <template> is matched
              depth-aware (nested <template x-for> / <template x-if> are kept inside the template).
        */
        public static function extractBlocks(string $content): array {
            $blocks = ['script' => '', 'header' => '', 'template' => '', 'cscript' => ''];
            $simple = [
                ['<script setup>', '</script>', 'script'],
                ['<script>', '</script>', 'script'],
                ['<header>', '</header>', 'header'],
                ['<cscript>', '</cscript>', 'cscript'],
            ];
            $len = strlen($content);
            $pos = 0;

            while ($pos < $len) {
                $lt = strpos($content, '<', $pos);
                if ($lt === false) break;

                if (substr($content, $lt, 4) === '<!--') {
                    $end = strpos($content, '-->', $lt + 4);
                    $pos = $end === false ? $len : $end + 3;
                    continue;
                }

                if (substr($content, $lt, 10) === '<template>') {
                    $start = $lt + 10;
                    $end = self::findTemplateEnd($content, $start);
                    if ($blocks['template'] === '') {
                        $blocks['template'] = substr($content, $start, $end - $start);
                    }
                    $pos = $end + strlen('</template>');
                    continue;
                }

                $matched = false;
                foreach ($simple as [$open, $close, $name]) {
                    if (substr($content, $lt, strlen($open)) === $open) {
                        $start = $lt + strlen($open);
                        $end = strpos($content, $close, $start);
                        if ($end === false) $end = $len;
                        if ($blocks[$name] === '') {
                            $blocks[$name] = substr($content, $start, $end - $start);
                        }
                        $pos = $end + strlen($close);
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) $pos = $lt + 1;
            }

            return $blocks;
        }

        private static function findTemplateEnd(string $content, int $from): int {
            $depth = 1;
            $offset = $from;
            $pattern = '/<!--.*?-->|<\?(?:php|=).*?\?>|<template(?=[\s>\/])|<\/template\s*>/is';

            while (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $token = $m[0][0];
                $at = $m[0][1];
                $offset = $at + strlen($token);

                if ($token[1] === '!' || $token[1] === '?') continue;

                if ($token[1] === '/') {
                    if (--$depth === 0) return $at;
                } else {
                    $depth++;
                }
            }

            return strlen($content);
        }
        /*  ================ END | VERSION 0.0.3 | END ================ */

        public function convertPVueToPHP($pvueContent, $bRoot = false, $fileName = '') {
            $this->currentPageName = basename($fileName, '.pvue');
            if (empty($this->currentPageName)) {
                error_log("Warning: Empty page name for file: $fileName");
            }

            $blocks = self::extractBlocks($pvueContent);

            $script = $this->processAjaxAnnotations($blocks['script'], $this->currentPageName);

            $header = $blocks['header'];
            $template = $blocks['template'];
            $cscript = $blocks['cscript'];

            $componentMap = [];
            $requiredComponents = [];

            $requireResult = $this->handleRequires($script, $bRoot);
            $script = $requireResult['script'];
            $componentMap = $requireResult['components'];
            $requiredComponents = $requireResult['required'];

            if ($bRoot) {
                $script = $this->injectDynamicHeaderLogic($script);
                $script = $this->injectRoutingLogic($script);
            }

            $convertedTemplate = $this->convertVueSyntax($template);

            $usedComponents = $this->findComponentsInTemplate($template);
            $missingComponents = array_diff($usedComponents, $requiredComponents);

            if (!empty($missingComponents)) {
                $warning = "// WARNING: The following components were used but not required: " . implode(', ', $missingComponents);
                $script = $warning . "\n" . $script;
            }

            $convertedTemplate = $this->injectComponents($convertedTemplate, $componentMap);

            if ($bRoot) {
                $convertedTemplate = $this->injectPageContent($convertedTemplate);
            }

            $convertedCscript = $this->handleCscript($cscript);

            $output = $this->buildOutput($script, $convertedTemplate, $convertedCscript, $bRoot, $header);

            return $output;
        }

        public function preProcessForAjax($pvueContent, $fileName = '') {
            $this->currentPageName = basename($fileName, '.pvue');
            $script = self::extractBlocks($pvueContent)['script'];

            $this->processAjaxAnnotations($script, $this->currentPageName);

            return $this;
        }

        public function getAjaxFunctions() {
            return $this->ajaxFunctions;
        }

        public function getAjaxHandling() {
            return $this->ajaxHandlingCode;
        }

        public function setAjaxData($functions, $handling) {
            $this->ajaxFunctions = $functions;
            $this->ajaxHandlingCode = $handling;
        }

        private function processAjaxAnnotations($scriptContent, $pageName) {
            if (!isset($this->ajaxFunctions[$pageName])) {
                $this->ajaxFunctions[$pageName] = [];
            }

            /*  ================ VERSION 0.0.3 ================
                - @AJAX("POST") / @AJAX('GET') (either quote), parameter defaults with parentheses,
                  and return types (function save($input): void {) are now recognised. */
            $pattern = '/@AJAX\(\s*[\'"]([A-Za-z]+)[\'"]\s*\)\s*(function\s+\w+\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)\s*(?::\s*\??[\w\\\\|]+)?)\s*\{/s';
            /*  ================ END | VERSION 0.0.3 | END ================ */

            if (preg_match_all($pattern, $scriptContent, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                $functionsToRemove = [];

                foreach ($matches as $match) {
                    $httpMethod = strtoupper($match[1][0]);
                    $functionHeader = $match[2][0];
                    $annotationStartPos = $match[0][1];
                    $bracePos = $annotationStartPos + strlen($match[0][0]);

                    $functionBody = $this->extractCompleteFunctionBody($scriptContent, $bracePos);

                    if ($functionBody !== null) {
                        $completeFunction = $functionHeader . "{" . $functionBody . "}";

                        if (preg_match('/function\s+(\w+)/', $completeFunction, $funcNameMatch)) {
                            $functionName = $funcNameMatch[1];

                            $this->ajaxFunctions[$pageName][$functionName] = [
                                'code' => trim($completeFunction),
                                'method' => $httpMethod
                            ];

                            $fullFunctionStart = $annotationStartPos;
                            $fullFunctionEnd = $bracePos + strlen($functionBody) + 1;

                            $functionsToRemove[] = [
                                'start' => $fullFunctionStart,
                                'end' => $fullFunctionEnd,
                                'name' => $functionName
                            ];
                        }
                    }
                }

                usort($functionsToRemove, function($a, $b) {
                    return $b['start'] - $a['start'];
                });

                foreach ($functionsToRemove as $remove) {
                    $length = $remove['end'] - $remove['start'];
                    $replacement = "// AJAX function '{$remove['name']}' handled separately";
                    $scriptContent = substr_replace($scriptContent, $replacement, $remove['start'], $length);
                }
            }

            return $scriptContent;
        }

        private function extractCompleteFunctionBody($content, $startAfterBrace) {
            $braceCount = 1;
            $pos = $startAfterBrace;
            $length = strlen($content);

            while ($pos < $length && $braceCount > 0) {
                $char = $content[$pos];

                if ($char === '"' || $char === "'") {
                    $stringChar = $char;
                    $pos++;

                    while ($pos < $length) {
                        if ($content[$pos] === '\\') {
                            $pos += 2;
                            continue;
                        }

                        if ($content[$pos] === $stringChar) {
                            break;
                        }
                        $pos++;
                    }
                }

                elseif ($char === '/' && $pos + 1 < $length) {
                    $nextChar = $content[$pos + 1];

                    if ($nextChar === '/') {
                        $pos += 2;
                        while ($pos < $length && $content[$pos] !== "\n") {
                            $pos++;
                        }
                    }
                    elseif ($nextChar === '*') {
                        $pos += 2;
                        while ($pos < $length - 1) {
                            if ($content[$pos] === '*' && $content[$pos + 1] === '/') {
                                $pos += 2;
                                break;
                            }
                            $pos++;
                        }
                    }
                }

                else {
                    if ($char === '{') {
                        $braceCount++;
                    }
                    elseif ($char === '}') {
                        $braceCount--;

                        if ($braceCount === 0) {
                            $bodyLength = $pos - $startAfterBrace;
                            return substr($content, $startAfterBrace, $bodyLength);
                        }
                    }
                }

                $pos++;
            }

            return null;
        }

        /*  ================ VERSION 0.0.3 ================
            - PHP that registers @AJAX handlers: the function definitions plus the allow-list entry
              ($GLOBALS['phpue_ajax'][name] = METHOD) the dispatcher checks. */
        private function buildAjaxRegistrationCode(array $functions, string $indent = ''): string {
            $code = '';
            foreach ($functions as $functionName => $functionData) {
                $code .= $indent . str_replace("\n", "\n" . $indent, $functionData['code']) . "\n";
                $code .= $indent . "\$GLOBALS['phpue_ajax']['" . $functionName . "'] = '" . $functionData['method'] . "';\n\n";
            }
            return $code;
        }
        /*  ================ END | VERSION 0.0.3 | END ================ */

        public function generateAjaxFiles() {
            $ajaxDir = '.dist/ajax';
            if (!is_dir($ajaxDir)) {
                mkdir($ajaxDir, 0755, true);
            }

            foreach ($this->ajaxFunctions as $pageName => $functions) {
                if (empty($pageName)) continue;

                $bView = is_file('views/' . $pageName . '.pvue') || isset($this->routing->routes[$pageName]);
                $bApp = ($pageName === 'App');

                /*  ================ VERSION 0.0.3 ================
                    - Components' @AJAX handlers are kept (written to ajax-component-<Name>.php);
                      views no longer need #require to get their handlers built. */
                if (!$bView && !$bApp && empty($functions)) {
                    continue;
                }
                $fileName = ($bView || $bApp) ? "ajax-$pageName.php" : "ajax-component-$pageName.php";
                /*  ================ END | VERSION 0.0.3 | END ================ */

                $ajaxContent = "<?php\n";
                $ajaxContent .= "// AJAX handlers for $pageName\n";
                $ajaxContent .= $this->buildAjaxRegistrationCode($functions);

                if (isset($this->ajaxHandlingCode[$pageName])) {
                    $ajaxContent .= "// AJAX/POST Request Handling for $pageName\n";
                    $ajaxContent .= "if (\$_SERVER['REQUEST_METHOD'] === 'POST') {\n";
                    $indentedAjaxCode = preg_replace('/^/m', '    ', $this->ajaxHandlingCode[$pageName]);
                    $ajaxContent .= $indentedAjaxCode;
                    $ajaxContent .= "}\n";
                }

                $ajaxFile = $ajaxDir . "/" . $fileName;
                file_put_contents($ajaxFile, $ajaxContent);
                echo "✅ Generated AJAX: $ajaxFile\n";
            }
        }

        private function injectRoutingLogic($scriptContent) {
            $routingLogic = <<<'PHP'
                $current_route = $_GET['page'] ?? 'index';
                $available_routes = array_keys(get_phpue_routing()->routes);

                if (!in_array($current_route, $available_routes)) {
                    http_response_code(404);
                    $current_route = 'index';
                }

                $GLOBALS['phpue_current_route'] = $current_route;
            PHP;

            return $routingLogic . "\n" . $scriptContent;
        }

        private function injectPageContent($template) {
            $pageInjectionLogic = <<<'PHP'
                <?php
                    $routing = get_phpue_routing();
                    echo $routing->getCurrentPageContent();
                ?>
            PHP;

            // 0.0.3: <View/>, <View />, <View></View>
            return preg_replace_callback('/<View\s*\/>|<View\s*>\s*<\/View\s*>/', function () use ($pageInjectionLogic) {
                return $pageInjectionLogic;
            }, $template);
        }

        private function findComponentsInTemplate($template) {
            preg_match_all('/<(\w+)\s*>\s*<\/\1\s*>|<(\w+)\s*\/>/', $template, $matches);

            $components = [];
            if (!empty($matches[1])) {
                $components = array_merge($components, array_filter($matches[1]));
            }
            if (!empty($matches[2])) {
                $components = array_merge($components, array_filter($matches[2]));
            }

            $htmlTags = ['div', 'span', 'p', 'a', 'button', 'input', 'form', 'img', 'ul', 'li', 'nav', 'header', 'footer', 'main', 'section', 'article', 'View',
                         'br', 'hr', 'td', 'th', 'tr', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'textarea', 'select', 'option', 'i', 'b', 'strong', 'em', 'canvas', 'iframe', 'source', 'meta', 'link'];
            $components = array_diff($components, $htmlTags);

            return array_unique($components);
        }

        private function injectComponents($template, $componentMap) {
            foreach ($componentMap as $componentName => $componentContent) {
                if ($componentName === '__view_components') continue;

                // 0.0.3: <Name/>, <Name />, <Name></Name> (callback = no $ / \ surprises in the compiled code)
                $name = preg_quote($componentName, '/');
                $template = preg_replace_callback('/<' . $name . '\s*\/>|<' . $name . '\s*>\s*<\/' . $name . '\s*>/', function () use ($componentContent) {
                    return $componentContent;
                }, $template);
            }

            return $template;
        }

        private function injectDynamicHeaderLogic($scriptContent) {
            $dynamicHeaderLogic = <<<'PHP'
                $current_route = $_GET['page'] ?? 'index';

                $routing = get_phpue_routing();
                $route_meta = $routing->getRouteMeta($current_route);

                $current_header = $phpue_header ?? '';

                if (!empty($route_meta)) {
                    $route_header = $routing->buildHeaderFromMeta($route_meta);
                    if (!empty($route_header)) {
                        $current_header = $route_header . "\n" . $current_header;
                    }
                }
            PHP;

            return $dynamicHeaderLogic . "\n" . $scriptContent;
        }

        public function getRouting()
        { return $this->routing; }

        private function extractBetween($content, $startTag, $endTag) {
            $pattern = '/' . preg_quote($startTag, '/') . '(.*?)' . preg_quote($endTag, '/') . '/s';
            preg_match($pattern, $content, $matches);
            return $matches[1] ?? '';
        }

        private function handleRequires($scriptContent) {
            // 0.0.3: single or double quotes
            preg_match_all('/@require\s+(\w+)\s+[\'"]([^\'"]+)[\'"]\s*;?/', $scriptContent, $componentRequires, PREG_SET_ORDER);

            $componentMap = [];
            $requiredComponents = [];

            foreach ($componentRequires as $match) {
                $componentName = $match[1];
                $componentPath = $match[2];
                $requiredComponents[] = $componentName;

                if (file_exists($componentPath)) {
                    $componentContent = file_get_contents($componentPath);

                    $compiledComponent = $this->convertPVueToPHP($componentContent, false, $componentPath);

                    $componentMap[$componentName] = $compiledComponent;

                    $scriptContent = str_replace($match[0], "// Component '$componentName' loaded from '$componentPath'", $scriptContent);
                } else {
                    $scriptContent = str_replace($match[0], "// ERROR: Component file not found: $componentPath", $scriptContent);
                }
            }

            $lines = explode("\n", $scriptContent);
            $cleanScriptContent = [];

            foreach ($lines as $line) {
                if (preg_match('/^\s*#require\s+[\'"]([^\'"]+\.pvue)[\'"]\s*;?\s*$/', $line, $matches)) {
                    $viewPath = $matches[1];

                    if (file_exists($viewPath)) {
                        $this->routing->addView($viewPath);
                        $cleanScriptContent[] = "// Added view to routing: $viewPath";
                    } else {
                        $cleanScriptContent[] = "// ERROR: View file not found: $viewPath";
                    }
                } else {
                    $cleanScriptContent[] = $line;
                }
            }

            $scriptContent = implode("\n", $cleanScriptContent);

            return [
                'script' => $scriptContent,
                'components' => $componentMap,
                'required' => $requiredComponents
            ];
        }

        private function convertVueSyntax($template) {
            /*  ================ VERSION 0.0.3 ================
                - The bare-<header> strip is gone: the header block is located at the top level of
                  the file, so a <header> element inside <template> is real markup and is kept.
                - Directives run FIRST (tag-aware), then {{ }}.
            */
            $template = $this->convertDirectives($template);

            $template = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/', function ($matches) {
                $expression = $matches[1];

                if ($expression !== '' && $expression[0] === '$') {
                    return '<?= htmlspecialchars(' . $expression . ' ?? "") ?>';
                }

                // {{ count($items) }}, {{ strtoupper($name) }}, {{ \App\Format::money($p) }}
                if (preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*\s*(\(|::)/', $expression)) {
                    return '<?= htmlspecialchars((string) (' . $expression . ')) ?>';
                }

                return $matches[0];
            }, $template);
            /*  ================ END | VERSION 0.0.3 | END ================ */

            $template = preg_replace_callback('/p-model="(\$[^"]*)"/',
                function($matches) {
                    $variable = trim($matches[1]);
                    return "name=\"".substr($variable,1)."\" value=\"<?= htmlspecialchars($variable ?? '') ?>\"";
                },
                $template);

            return $template;
        }

        /*  ================ VERSION 0.0.3 ================
            - Tag-aware p-if / p-for. Replaces the 0.0.2 line-based p-if stack and the p-for regex.
              Each pass takes the LAST opening tag that carries a directive (so nested directives
              resolve inner-first), finds its real closing tag by counting same-name tags, and wraps
              the element. Scanning happens on a "masked" copy where PHP blocks, comments, {{ }} and
              <script>/<style> bodies are blanked out, so their contents can't confuse the scanner.
        */
        private function maskOpaqueRegions(string $template): string {
            return preg_replace_callback(
                '/<\?(?:php|=).*?\?>|<!--.*?-->|\{\{.*?\}\}|(<(script|style)\b[^>]*>)(.*?)(<\/\2\s*>)/is',
                function ($m) {
                    if (!empty($m[1])) {
                        return $m[1] . str_repeat(' ', strlen($m[3])) . $m[4];
                    }
                    return str_repeat(' ', strlen($m[0]));
                },
                $template
            );
        }

        /** Offset just after the '>' that closes the tag starting at $lt (quote-aware), or null. */
        private function scanTagEnd(string $masked, int $lt, &$selfClosing = null): ?int {
            $length = strlen($masked);
            $quote = null;
            for ($i = $lt + 1; $i < $length; $i++) {
                $char = $masked[$i];
                if ($quote !== null) {
                    if ($char === $quote) $quote = null;
                    continue;
                }
                if ($char === '"' || $char === "'") {
                    $quote = $char;
                    continue;
                }
                if ($char === '>') {
                    $selfClosing = $masked[$i - 1] === '/';
                    return $i + 1;
                }
            }
            return null;
        }

        /** Offset just after the matching </tag>, counting nested same-name tags, or null.
            Walks tag by tag (jumping over each opening tag's attributes), so markup that only
            appears inside attribute values (x-html="'<p></p>'", x-show="a<b") is never counted. */
        private function findElementEnd(string $masked, int $from, string $tag): ?int {
            $depth = 1;
            $offset = $from;

            while (preg_match('/<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)/', $masked, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $at = $m[0][1];
                $name = strtolower($m[2][0]);

                if ($m[1][0] === '/') {
                    $close = strpos($masked, '>', $at);
                    if ($close === false) return null;
                    if ($name === $tag && --$depth === 0) return $close + 1;
                    $offset = $close + 1;
                    continue;
                }

                $end = $this->scanTagEnd($masked, $at, $selfClosing);
                if ($end === null) return null;
                if ($name === $tag && !$selfClosing) $depth++;
                $offset = $end;
            }
            return null;
        }

        private function convertDirectives(string $template): string {
            $directivePattern = '/\s(p-for|p-if)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/';

            for ($guard = 0; $guard < 10000; $guard++) {
                $masked = $this->maskOpaqueRegions($template);
                // Tokenise opening tags left to right (jumping over attributes), keep the LAST one
                // that carries a directive, so nested directives resolve inner-first.
                $found = null;
                $offset = 0;
                while (preg_match('/<([a-zA-Z][a-zA-Z0-9:-]*)(?=[\s\/>])/', $masked, $m, PREG_OFFSET_CAPTURE, $offset)) {
                    $lt = $m[0][1];
                    $end = $this->scanTagEnd($masked, $lt, $selfClosing);
                    if ($end === null) break;
                    $openTag = substr($template, $lt, $end - $lt);
                    if (preg_match_all($directivePattern, $openTag, $directives, PREG_SET_ORDER)) {
                        // p-for wraps outside p-if when both sit on one element (filter per item)
                        usort($directives, fn($a, $b) => strcmp($a[1], $b[1]));
                        $found = [$lt, strtolower($m[1][0]), $end, $selfClosing, $openTag, $directives[0]];
                    }
                    $offset = $end;
                }
                if ($found === null) break;

                [$lt, $tag, $end, $selfClosing, $openTag, $directive] = $found;
                $kind = $directive[1];
                $value = ($directive[2] ?? '') !== '' ? $directive[2] : ($directive[3] ?? '');
                $newOpenTag = preg_replace('/\s+' . $kind . '\s*=\s*(?:"[^"]*"|\'[^\']*\')/', '', $openTag, 1);

                if ($selfClosing || in_array($tag, self::VOID_TAGS, true)) {
                    $elementEnd = $end;
                } else {
                    $elementEnd = $this->findElementEnd($masked, $end, $tag);
                    if ($elementEnd === null) {
                        error_log("PHPue: <$tag $kind=\"$value\"> has no closing </$tag>; wrapping the opening tag only.");
                        $elementEnd = $end;
                    }
                }

                $element = $newOpenTag . substr($template, $end, $elementEnd - $end);

                if ($kind === 'p-if') {
                    $wrapped = '<?php if(' . $value . '): ?>' . $element . '<?php endif; ?>';
                } elseif (preg_match('/^\s*(.+?)\s+in\s+(.+?)\s*$/s', $value, $loop)) {
                    $wrapped = '<?php foreach (phpue_iterable((' . $loop[2] . ') ?? null) as ' . $loop[1] . '): ?>' . $element . '<?php endforeach; ?>';
                } else {
                    error_log("PHPue: invalid p-for=\"$value\" (expected \"\$item in \$items\").");
                    $wrapped = preg_replace('/\s+p-for\s*=/', ' data-phpue-invalid-p-for=', $openTag, 1) . substr($template, $end, $elementEnd - $end);
                }

                $template = substr($template, 0, $lt) . $wrapped . substr($template, $elementEnd);
            }

            return $template;
        }
        /*  ================ END | VERSION 0.0.3 | END ================ */

        private function handleCscript($cscript) {
            if (empty($cscript)) return '';

            // 0.0.3: any $expression ({{ $data['items'] }}, {{ $user->profile }}), not just $var
            $cscript = preg_replace_callback(
                '/\{\{\s*(\$.+?)\s*\}\}/',
                function($matches) {
                    $phpVar = $matches[1];
                    return "<?= json_encode($phpVar, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>";
                },
                $cscript
            );

            $bModule = preg_match('/^\s*(import|export)\s+/m', $cscript);

            if ($bModule) {
                return "<script type=\"module\">\n" . $cscript . "\n</script>";
            } else {
                return "<script>\n" . $cscript . "\n</script>";
            }
        }

        private function buildOutput($script, $template, $cscript, $bRoot, $header = '') {
            $output = "<?php\n";

            /*  ================ VERSION 0.0.2 ================
                - Added autoload.php detection!
                - Use bruteforce to load classes,
                - or find autoload.php to choose a order by vendor/ or the developer!
                - Stops the "class has already been declared" bug. Happy Days!
                ================ VERSION 0.0.3 ================
                - Files load in SORTED path order (was filesystem order, which differs per OS/disk),
                  and the loader's variables are prefixed so they can't clobber page variables.
            */
            $output .= "// Auto-load backend classes\n";
            $output .= "// Determine correct backend path for current environment\n";
            $output .= "if (defined('PHPUE_BUILD_MODE') && PHPUE_BUILD_MODE === true) {\n";
            $output .= "    // Build mode: use source backend\n";
            $output .= "    \$phpueBackendDir = 'backend';\n";
            $output .= "} else {\n";
            $output .= "    // Runtime: check if we're in development or production\n";
            $output .= "    \$phpueBackendDir = is_dir('.dist/backend') ? '.dist/backend' : 'backend';\n";
            $output .= "}\n";
            $output .= "if (is_dir(\$phpueBackendDir)) {\n";
            $output .= "    \$phpueBackendFiles = [];\n";
            $output .= "    \$phpueBackendIterator = new RecursiveIteratorIterator(\n";
            $output .= "        new RecursiveCallbackFilterIterator(\n";
            $output .= "            new RecursiveDirectoryIterator(\$phpueBackendDir, RecursiveDirectoryIterator::SKIP_DOTS),\n";
            $output .= "            function (\$current, \$key, \$iterator) use (&\$phpueBackendFiles) {\n";
            $output .= "                // If this directory has autoload.php → load only that file and skip deeper scan\n";
            $output .= "                if (\$current->isDir()) {\n";
            $output .= "                    \$autoload = \$current->getPathname() . '/autoload.php';\n";
            $output .= "                    if (file_exists(\$autoload)) {\n";
            $output .= "                        \$phpueBackendFiles[] = \$autoload;\n";
            $output .= "                        return false; // Do not go deeper\n";
            $output .= "                    }\n";
            $output .= "                }\n";
            $output .= "                return true; // Continue scanning\n";
            $output .= "            }\n";
            $output .= "        ),\n";
            $output .= "        RecursiveIteratorIterator::SELF_FIRST\n";
            $output .= "    );\n";
            $output .= "\n";
            $output .= "    foreach (\$phpueBackendIterator as \$phpueBackendFile) {\n";
            $output .= "        if (\$phpueBackendFile->isFile() && \$phpueBackendFile->getExtension() === 'php') {\n";
            $output .= "            \$phpueBackendFiles[] = \$phpueBackendFile->getPathname();\n";
            $output .= "        }\n";
            $output .= "    }\n";
            $output .= "    sort(\$phpueBackendFiles, SORT_STRING);\n";
            $output .= "    foreach (\$phpueBackendFiles as \$phpueBackendFile) {\n";
            $output .= "        require_once \$phpueBackendFile;\n";
            $output .= "    }\n";
            $output .= "    unset(\$phpueBackendFiles, \$phpueBackendIterator, \$phpueBackendFile);\n";
            $output .= "}\n\n";
            /*  ================ END | VERSION 0.0.3 | END ================ */

            // Pre-determine 404 status for header injection
            $output .= "\$current_route = \$_GET['page'] ?? 'index';\n";
            $output .= "\$routing = get_phpue_routing();\n";
            $output .= "\$b404 = !isset(\$routing->routes[\$current_route]);\n";
            $output .= "if (\$b404) {\n";
            $output .= "    if (file_exists('httpReqs/http404Head.php')) {\n";
            $output .= "        include 'httpReqs/http404Head.php';\n";
            $output .= "        \$GLOBALS['phpue_http404_header'] = \$phpue_header ?? '';\n";
            $output .= "    } else {\n";
            $output .= "        // Fallback headers when httpReqs/ doesn't exist\n";
            $output .= "        \$GLOBALS['phpue_http404_header'] = <<<HTML\n";
            $output .= "            <title>404 - Page Not Found</title>\n";
            $output .= "            <meta name=\"description\" content=\"The page you're looking for doesn't exist\">\n";
            $output .= "            <meta name=\"keywords\" content=\"404, page not found\">\n";
            $output .= "        HTML;\n";
            $output .= "    }\n";
            $output .= "}\n";

            if ($bRoot) {
                /*  ================ VERSION 0.0.3 ================
                    - Session policy: PHPUE_SESSION 'auto' (default, = 0.0.2) or 'lazy' (only resume an
                      existing session; anonymous pages stay cacheable). A script that calls
                      session_start() itself still opts out, as before. */
                if (!str_contains($script, 'session_start()')) {
                    $output .= "// Session policy (PHPUE_SESSION: 'auto' | 'lazy')\n";
                    $output .= "phpue_session();\n";
                }
                /*  ================ END | VERSION 0.0.3 | END ================ */

                $output .= "// Load AJAX handlers\n";
                $output .= "\$ajaxFiles = glob('.dist/ajax/ajax-*.php');\n";
                $output .= "if (!empty(\$ajaxFiles)) {\n";
                $output .= "    // Production mode: Load from pre-compiled files\n";
                $output .= "    foreach (\$ajaxFiles as \$ajaxFile) {\n";
                $output .= "        require_once \$ajaxFile;\n";
                $output .= "    }\n";

                if (defined('PHPUE_BUILD_MODE') && PHPUE_BUILD_MODE === true) {
                    $output .= "}\n";
                } else {
                    $output .= "} else {\n";
                    $output .= "    // Development mode: Load AJAX functions directly\n";

                    foreach ($this->ajaxFunctions as $pageName => $functions) {
                        $output .= $this->buildAjaxRegistrationCode($functions, '    ');
                    }
                    $output .= "}\n";
                }

                $output .= "\n";

                /*  ================ VERSION 0.0.3 ================
                    - Only functions registered with @AJAX can be called (was: ANY existing function,
                      including PHP built-ins), and the declared method is enforced (405 otherwise).
                    - Unknown actions still fall through to normal page rendering. */
                $output .= "// Handle AJAX requests (must be before routing)\n";
                $output .= "if (\$_SERVER['REQUEST_METHOD'] === 'POST' || \$_SERVER['REQUEST_METHOD'] === 'GET') {\n";
                $output .= "    \$input = [];\n";
                $output .= "    if (\$_SERVER['REQUEST_METHOD'] === 'POST') {\n";
                $output .= "        \$input = json_decode(file_get_contents('php://input'), true) ?? [];\n";
                $output .= "        if (!is_array(\$input)) \$input = [];\n";
                $output .= "    } else {\n";
                $output .= "        // GET requests - use query parameters\n";
                $output .= "        \$input = \$_GET;\n";
                $output .= "    }\n";
                $output .= "    \n";
                $output .= "    if (isset(\$input['action']) && is_string(\$input['action']) && isset(\$GLOBALS['phpue_ajax'][\$input['action']])) {\n";
                $output .= "        \$action = \$input['action'];\n";
                $output .= "        \$declaredMethod = \$GLOBALS['phpue_ajax'][\$action];\n";
                $output .= "        if (\$declaredMethod !== \$_SERVER['REQUEST_METHOD']) {\n";
                $output .= "            http_response_code(405);\n";
                $output .= "            header('Allow: ' . \$declaredMethod);\n";
                $output .= "            header('Content-Type: application/json');\n";
                $output .= "            echo json_encode(['error' => \"@AJAX '\$action' only accepts \$declaredMethod\"]);\n";
                $output .= "            exit;\n";
                $output .= "        }\n";
                $output .= "        if (function_exists(\$action)) {\n";
                $output .= "            if (\$_SERVER['REQUEST_METHOD'] === 'POST') phpue_session(true);\n";
                $output .= "            \$reflection = new ReflectionFunction(\$action);\n";
                $output .= "            \$paramCount = \$reflection->getNumberOfParameters();\n";
                $output .= "            \n";
                $output .= "            if (\$paramCount > 0) {\n";
                $output .= "                \$action(\$input);\n";
                $output .= "            } else {\n";
                $output .= "                \$action();\n";
                $output .= "            }\n";
                $output .= "            exit;\n";
                $output .= "        }\n";
                $output .= "    }\n";
                $output .= "}\n\n";
                /*  ================ END | VERSION 0.0.3 | END ================ */

                $output .= $script . "\n";
            } else {
                $output .= $script . "\n";
            }

            if (!empty($header)) {
                $processedHeader = $this->routing->processHeaderTemplates($header);

                $output .= "\$phpue_header = <<<HTML\n{$processedHeader}\nHTML;\n";
            }

            $output .= "?>\n";

            if ($bRoot) {
                /*  ================ VERSION 0.0.2 ================
                    - Added HTML LANG (instead of defaulting to 'en')
                    - Added Static View Langs (use <header><meta name="lang" content="br"></header>)
                    ================ VERSION 0.0.3 ================
                    - Resolved at RUNTIME (was baked in at build time, so .dist pages all shared
                      one lang): phpue_set_lang() > view <meta name="lang"> > PHPUE_LANG.
                */
                $output .= "<!DOCTYPE html>\n";
                $output .= "<html lang=\"<?= htmlspecialchars(phpue_html_lang()) ?>\">\n";
                /*  ================ END | VERSION 0.0.3 | END ================ */
                $output .= "<head>\n";
                $output .= "<?php\n";
                $output .= "// Output main App header\n";
                $output .= "if (isset(\$phpue_header)) {\n";
                $output .= "    echo \$phpue_header;\n";
                $output .= "}\n";
                $output .= "\n";
                $output .= "// Output view-specific header\n";
                $output .= "\$current_route = \$_GET['page'] ?? 'index';\n";
                $output .= "\$route_meta = \$routing->getRouteMeta(\$current_route);\n";
                $output .= "if (!empty(\$route_meta)) {\n";
                $output .= "    \$view_header = \$routing->buildHeaderFromMeta(\$route_meta);\n";
                $output .= "    if (!empty(\$view_header)) {\n";
                $output .= "        echo \"\\n\" . \$view_header;\n";
                $output .= "    }\n";
                $output .= "}\n";
                $output .= "// Output 404 header if needed\n";
                $output .= "echo \$GLOBALS['phpue_http404_header'] ?? '';\n";
                $output .= "?>\n";
                $output .= "</head>\n";
                $output .= "<body>\n";
                $output .= $template . "\n";
                $output .= $cscript . "\n";
                $output .= "</body>\n";
                $output .= "</html>\n";
            } else {
                $output .= $template . "\n";
                $output .= $cscript . "\n";
            }

            return $output;
        }
    }

    function get_phpue_converter() {
        static $converter = null;
        if ($converter === null) {
            $converter = new PHPueConverter();
        }
        return $converter;
    }

    function convert_pvue_file($pvueFilePath, $bRoot = false) {
        $converter = get_phpue_converter();

        if (!file_exists($pvueFilePath)) {
            throw new Exception("PVue file not found: $pvueFilePath");
        }

        $content = file_get_contents($pvueFilePath);
        return $converter->convertPVueToPHP($content, $bRoot, $pvueFilePath);
    }

    function get_phpue_routing() {
        static $converter = null;
        if ($converter === null) {
            $converter = new PHPueConverter();

            $routing = $converter->getRouting();

            // NEW: Check both locations for compiled pages
            $compiledPages = [];
            if (is_dir('.dist/pages')) {
                $compiledPages = array_merge($compiledPages, glob('.dist/pages/*.php'));
            }
            if (is_dir('pages')) {
                $compiledPages = array_merge($compiledPages, glob('pages/*.php'));
            }

            if (!empty($compiledPages)) {
                foreach ($compiledPages as $page) {
                    $routing->addCompiledView($page);
                }
            } else {
                $views = glob('views/*.pvue');
                foreach ($views as $view) {
                    $routing->addView($view);
                }
            }

            $currentRoute = $_GET['page'] ?? 'index';

            if (empty($compiledPages)) {
                $sourceFile = $routing->routes[$currentRoute]['file'] ?? 'views/index.pvue';
                $routing->preProcessCurrentPage($sourceFile);
            }
        }
        return $converter->getRouting();
    }

    function phpue_navigation($currentPage = null) {
        $routing = get_phpue_routing();
        return $routing->getNavigation();
    }

    function phpue_current_meta() {
        $routing = get_phpue_routing();
        $currentPage = $_GET['page'] ?? 'index';
        return $routing->getRouteMeta($currentPage);
    }

    /*  ================ VERSION 0.0.3 ================
        - Runtime helpers used by compiled output (conversion.php is always loaded by index.php,
          including in .dist mode, so compiled files can rely on them).
    */

    /** p-for source normaliser: arrays and Traversables iterate, anything else renders nothing. */
    function phpue_iterable($value): iterable {
        return is_iterable($value) ? $value : [];
    }

    /**
     * Session policy. PHPUE_SESSION = 'auto' (default, 0.0.2 behaviour: always start) or 'lazy'
     * (only resume an existing session, start one when $write is true). Extensions that need
     * $_SESSION can call this instead of session_start(); it exists before backend/ is loaded.
     */
    function phpue_session(bool $write = false): bool {
        if (session_status() === PHP_SESSION_ACTIVE) return true;
        if (session_status() === PHP_SESSION_DISABLED || headers_sent()) return false;

        $bLazy = defined('PHPUE_SESSION') && PHPUE_SESSION === 'lazy';
        if (!$bLazy || $write || isset($_COOKIE[session_name()])) {
            return @session_start();
        }
        return false;
    }

    /** Set the document language for this request (e.g. from a language selector). */
    function phpue_set_lang(?string $lang): void {
        $GLOBALS['phpue_lang'] = $lang;
    }

    /** <html lang>: phpue_set_lang() > current view's <meta name="lang"> > PHPUE_LANG > 'en'. */
    function phpue_html_lang(): string {
        if (!empty($GLOBALS['phpue_lang'])) return (string) $GLOBALS['phpue_lang'];

        $meta = phpue_current_meta();
        if (!empty($meta['lang'])) return (string) $meta['lang'];

        return defined('PHPUE_LANG') ? PHPUE_LANG : 'en';
    }
    /*  ================ END | VERSION 0.0.3 | END ================ */
?>
