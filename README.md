# kirby-SCSSizer

A Kirby CMS plugin that compiles SCSS to CSS on the fly, powered by [scssphp](https://scssphp.github.io/scssphp/).

---

## Credits & origins

This plugin is built on top of **scssphp** — a full SCSS compiler written in pure PHP, requiring no Node.js or Ruby. The library is maintained by the [scssphp organisation](https://github.com/scssphp/scssphp) and was originally created by [Leaf Corcoran](https://github.com/leafo) ([@leafot](https://leafo.net/)), then taken over by [Anthon Pang](https://github.com/robocoder) and [Cédric Morin](https://github.com/Cerdic).

The Kirby integration draws on earlier work I did adapting the `scssphp/scssphp` library (v1.11.0) for PHP 8 compatibility across several Kirby projects. This package is a clean rewrite of that integration, repackaged as a proper Composer/Kirby plugin.

- scssphp homepage: <https://scssphp.github.io/scssphp/>
- scssphp source: <https://github.com/scssphp/scssphp>
- scssphp license: MIT

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.1 |
| Kirby CMS | >= 3.9 |
| scssphp/scssphp | ^1.12 |

---

## Installation

### Via Composer (recommended)

```bash
composer require rllngr/kirby-scssizer
```

The [Kirby Composer installer](https://github.com/getkirby/composer-installer) will place the plugin in `site/plugins/kirby-scssizer` automatically.

### Manual

1. Clone or download this repository.
2. Run `composer install` inside the plugin directory to pull in `scssphp`.
3. Copy (or symlink) the entire folder to `site/plugins/kirby-scssizer`.

---

## Configuration

All options live under the `rllngr.kirby-scssizer` key in your project's `config.php`.

```php
// site/config/config.php
return [
    'rllngr.kirby-scssizer' => [

        // Required: map of SCSS source => CSS output
        // Paths are relative to the Kirby root directory
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],

        // Additional @import / @use search paths (optional)
        // The directory containing each source file is always included automatically
        'importPaths' => [
            'assets/scss/vendor',
        ],

        // SCSS variables to inject before compilation (optional)
        // Use the variable name without the leading $
        'variables' => [
            'color-primary' => '#3490dc',
            'font-size-base' => '16px',
        ],

        // Output style: 'expanded' | 'compressed' | null
        // null (default) = expanded when Kirby debug is true, compressed otherwise
        'outputStyle' => null,

        // Auto-recompile on change: true | false | null
        // null (default) = enabled when Kirby debug is true, disabled otherwise
        // In production (autoCompile = false), the CSS is compiled once on the first
        // request and never touched again until you clear the output file.
        'autoCompile' => null,
    ],
];
```

### Typical development setup

```php
// site/config/config.localhost.php
return [
    'debug' => true,
    // autoCompile and outputStyle are inferred from the debug flag automatically
];
```

```php
// site/config/config.php
return [
    'debug' => false,
    'rllngr.kirby-scssizer' => [
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],
    ],
];
```

---

## Usage

### Mode 1 — Explicit files (`files`)

Declare your source/output pairs in the config. Compilation is triggered automatically by the `route:before` hook on every request.

```php
// site/config/config.php
return [
    'rllngr.kirby-scssizer' => [
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],
    ],
];
```

To include the stylesheet in your templates, use Kirby's built-in helper:

```php
<?= css('assets/css/main.css') ?>
```

---

### Mode 2 — Template-based (`templates`)

The plugin automatically compiles `{scssDir}/{template}.scss` → `{cssDir}/{template}.css` for each page. If no SCSS file exists for the current template, it falls back to `default.scss`.

```php
// site/config/config.php
return [
    'rllngr.kirby-scssizer' => [
        'templates' => [
            'scssDir' => 'assets/scss',
            'cssDir'  => 'assets/css',
            'default' => 'default',
        ],
    ],
];
```

Call the snippet or the page method from **anywhere** in your templates — no restriction on file location:

```php
// From a layout, a nested snippet, or anywhere else:
<?php snippet('rllngr/kirby-scssizer') ?>

// Or via the page method:
<?= $page->cssTag() ?>
```

The `rllngr/kirby-scssizer` snippet is **registered by the plugin itself** — no file needs to be created in `site/snippets/`. You can call it from `site/snippets/includes/head.php`, `site/templates/default.php`, or wherever fits your project structure.

> **Migrating from `snippet('scss')`**: simply replace `<?php snippet('scss') ?>` with `<?php snippet('rllngr/kirby-scssizer') ?>` in your header or layout.

---

## How it works

1. On every request, the `route:before` hook calls `ScssCompiler::compileAll()`.
2. The compiler checks whether the SCSS source file **or any of its imported partials** is newer than the compiled CSS output. If nothing has changed, the function returns immediately — overhead is minimal.
3. When a change is detected (or the output file is missing), the source is compiled with [scssphp](https://github.com/scssphp/scssphp) and written to the configured output path.
4. The list of imported partials (`@use`, `@forward`, `@import`) is stored alongside the CSS output in a `.scss-deps` sidecar file, so that a change to any partial triggers a recompile.

---

## Manual compilation

You can trigger compilation programmatically from a template, controller, or CLI script:

```php
use Rllngr\SCSSizer\ScssCompiler;

// Compile all configured files
ScssCompiler::compileAll();

// Compile a single file
ScssCompiler::compile(
    sourcePath: 'assets/scss/main.scss',
    outputPath: 'assets/css/main.css',
);
```

---

## License

MIT — see [LICENSE](LICENSE) for details.
