# kirby-scss

A Kirby CMS plugin that compiles SCSS to CSS on the fly, powered by [scssphp](https://scssphp.github.io/scssphp/).

---

## Credits & origins

This plugin is built on top of **scssphp** — a full SCSS compiler written in pure PHP, requiring no Node.js or Ruby. The library is maintained by the [scssphp organisation](https://github.com/scssphp/scssphp) and was originally created by [Leaf Corcoran](https://github.com/leafo) ([@leafot](https://leafo.net/)), then taken over by [Anthon Pang](https://github.com/robocoder) and [Cédric Morin](https://github.com/Cerdic).

The Kirby integration draws on earlier work I did adapting the `scssphp/scssphp` library (v1.11.0) for PHP 8 compatibility in my own Kirby projects. This package is a clean rewrite of that integration, repackaged as a proper Composer/Kirby plugin.

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
composer require rllngr/kirby-scss
```

The [Kirby Composer installer](https://github.com/getkirby/composer-installer) will place the plugin in `site/plugins/kirby-scss` automatically.

### Manual

1. Clone or download this repository.
2. Run `composer install` inside the plugin directory to pull in `scssphp`.
3. Copy (or symlink) the entire folder to `site/plugins/kirby-scss`.

---

## Configuration

All options live under the `rllngr.scss` key in your project's `config.php`.

```php
// site/config/config.php
return [
    'rllngr.scss' => [

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
        // In production (autoCompile = false), the CSS is compiled once on first
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
    // autoCompile and outputStyle are inferred from debug automatically
];
```

```php
// site/config/config.php
return [
    'debug' => false,
    'rllngr.scss' => [
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],
    ],
];
```

---

## Usage

### Mode 1 — Fichiers explicites (`files`)

Déclarez les paires source/sortie dans la config. La compilation est déclenchée automatiquement par le hook `route:before`.

```php
// site/config/config.php
return [
    'rllngr.scss' => [
        'files' => [
            'assets/scss/main.scss' => 'assets/css/main.css',
        ],
    ],
];
```

Pour injecter le tag CSS dans vos templates, utilisez le helper Kirby classique :

```php
<?= css('assets/css/main.css') ?>
```

---

### Mode 2 — Par template (`templates`)

Le plugin compile automatiquement `{scssDir}/{template}.scss` → `{cssDir}/{template}.css` pour chaque page. Si aucun fichier SCSS spécifique au template n'existe, il utilise le fichier `default.scss`.

```php
// site/config/config.php
return [
    'rllngr.scss' => [
        'templates' => [
            'scssDir' => 'assets/scss',
            'cssDir'  => 'assets/css',
            'default' => 'default',
        ],
    ],
];
```

Appelez le snippet ou la page method depuis **n'importe quel endroit** de vos templates — plus de contrainte sur le chemin du fichier :

```php
// Depuis un layout, un snippet imbriqué, ou n'importe où :
<?php snippet('rllngr/scss') ?>

// Ou via la page method :
<?= $page->cssTag() ?>
```

Le snippet `rllngr/scss` est **enregistré par le plugin lui-même** — il n'y a aucun fichier à créer dans `site/snippets/`. Vous pouvez l'appeler depuis `site/snippets/includes/head.php`, `site/templates/default.php`, ou ailleurs.

> **Migration depuis `snippet('scss')`** : remplacez simplement `<?php snippet('scss') ?>` par `<?php snippet('rllngr/scss') ?>` dans votre header ou layout.

---

## How it works

1. On every request, the `route:before` hook calls `ScssCompiler::compileAll()`.
2. The compiler checks whether any of the SCSS source file **or its imported partials** is newer than the compiled CSS output. If nothing has changed, the function returns immediately — overhead is minimal.
3. When a change is detected (or the output file is missing), the source is compiled with [scssphp](https://github.com/scssphp/scssphp) and written to the configured output path.
4. The list of imported partials (`@use`, `@forward`, `@import`) is stored alongside the CSS output in a `.scss-deps` sidecar file so that changes to any partial trigger a recompile.

---

## Manual compilation

You can trigger compilation programmatically from a template, controller, or CLI script:

```php
use Rllngr\KirbyScss\ScssCompiler;

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
