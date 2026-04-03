# kirby-SCSSizer

A Kirby CMS plugin that compiles SCSS to CSS on the fly — no Node.js or Ruby required. Part of the **rllngr** plugin set.

## Features

- Compiles SCSS to CSS automatically on every request (only when source has changed)
- Two modes: explicit file mapping or per-template compilation
- Expanded output in debug mode, compressed in production — no config needed
- Change detection across all imported partials (`@use`, `@forward`, `@import`)
- SCSS variables injectable from `config.php`
- Built-in Kirby snippet and page method — place it anywhere in your templates
- Powered by [scssphp](https://github.com/scssphp/scssphp) (pure PHP, no external dependencies)

## Requirements

- Kirby 3.9+
- PHP 8.1+

## Installation

**Via Composer** (recommended — installs automatically into `site/plugins/`):

```bash
composer require rllngr/kirby-scssizer
```

**Manually** — download or clone into `site/plugins/kirby-scssizer`, then run `composer install` inside the folder.

## Configuration

In `site/config/config.php`:

```php
return [
    'rllngr.kirby-scssizer' => [

        // Explicit file pairs: SCSS source => CSS output (paths relative to Kirby root)
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],

        // Per-template mode: compiles {scssDir}/{template}.scss → {cssDir}/{template}.css
        'templates' => [
            'scssDir' => 'assets/scss',
            'cssDir'  => 'assets/css',
            'default' => 'default', // fallback when no template-specific file exists
        ],

        // Additional @use / @import search paths
        'importPaths' => [],

        // SCSS variables to inject (without the leading $)
        'variables' => [
            'color-primary' => '#3490dc',
        ],

        // 'expanded' | 'compressed' | null (null = auto from Kirby debug flag)
        'outputStyle' => null,

        // true | false | null (null = auto: enabled in debug, disabled in production)
        'autoCompile' => null,
    ],
];
```

## Usage

### Explicit files

Declare your source/output pairs in `files`. Compilation is triggered automatically — use Kirby's `css()` helper to include the output:

```php
<?= css('assets/css/main.css') ?>
```

### Per-template

Configure `templates` and call the built-in snippet or page method from anywhere in your layouts:

```php
<?php snippet('rllngr/kirby-scssizer') ?>

// or
<?= $page->cssTag() ?>
```

The snippet is registered by the plugin itself — no file needed in `site/snippets/`.

> **Migrating from `snippet('scss')`**: replace it with `snippet('rllngr/kirby-scssizer')`.

## Credits

Built on top of [scssphp](https://github.com/scssphp/scssphp) by [Leaf Corcoran](https://github.com/leafo), [Anthon Pang](https://github.com/robocoder) and [Cédric Morin](https://github.com/Cerdic) — MIT license.

## License

MIT — [Nicolas Rollinger](https://rollinger.design)
