<?php

declare(strict_types=1);

use Kirby\Cms\App as Kirby;
use Rllngr\SCSSizer\ScssCompiler;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('rllngr/kirby-scssizerizer', [

    /**
     * Default option values.
     *
     * Override any of these in your project's config.php:
     *
     *   return [
     *       'rllngr.kirby-scssizer' => [
     *           'files' => [
     *               'assets/scss/main.scss' => 'assets/css/main.css',
     *           ],
     *       ],
     *   ];
     */
    'options' => [
        // Explicit map of SCSS source => CSS output (paths relative to the Kirby root)
        'files' => [],

        // Template-based mode: compiles {scssDir}/{template}.scss → {cssDir}/{template}.css
        // on demand when $page->cssTag() or snippet('rllngr/kirby-scssizer') is called.
        'templates' => [
            'scssDir' => 'assets/scss',
            'cssDir'  => 'assets/css',
            'default' => 'default', // fallback when no template-specific file exists
        ],

        // Additional SCSS @use / @import search paths (beyond the source file's own directory)
        'importPaths' => [],

        // SCSS variables to inject: ['color-primary' => '#f00']
        'variables' => [],

        // 'expanded' | 'compressed' | null (null = auto: expanded in debug, compressed otherwise)
        'outputStyle' => null,

        // null = auto (true when Kirby debug is on, false otherwise)
        'autoCompile' => null,
    ],

    // -------------------------------------------------------------------------
    // Snippets
    // -------------------------------------------------------------------------

    /**
     * Built-in snippet registered under the plugin namespace.
     * Call it from any template or layout — no physical file required in your project:
     *
     *   <?php snippet('rllngr/kirby-scssizer') ?>
     *
     * The snippet compiles the page's SCSS if needed and outputs the <link> tag.
     */
    'snippets' => [
        'rllngr/kirby-scssizer' => __DIR__ . '/snippets/css-tag.php',
    ],

    // -------------------------------------------------------------------------
    // Page methods
    // -------------------------------------------------------------------------

    /**
     * $page->cssTag()
     *
     * Returns the compiled CSS <link> tag for the current page.
     * Useful when you prefer a method call over a snippet:
     *
     *   <?= $page->cssTag() ?>
     */
    'pageMethods' => [
        'cssTag' => function (): string {
            return ScssCompiler::cssTag($this);
        },
    ],

    // -------------------------------------------------------------------------
    // Hooks
    // -------------------------------------------------------------------------

    'hooks' => [
        /**
         * Compile all explicitly configured files on every request.
         * ScssCompiler::compileAll() is a no-op when nothing has changed,
         * so the overhead in production is negligible.
         */
        'route:before' => function (): void {
            ScssCompiler::compileAll();
        },
    ],

]);
