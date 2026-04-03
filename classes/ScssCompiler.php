<?php

declare(strict_types=1);

namespace Rllngr\KirbyScss;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

class ScssCompiler
{
    /**
     * Compile all SCSS files defined in the plugin configuration.
     */
    public static function compileAll(): void
    {
        $kirby = Kirby::instance();
        $files = $kirby->option('rllngr.scss.files', []);

        if (empty($files)) {
            return;
        }

        foreach ($files as $source => $output) {
            static::compile(sourcePath: $source, outputPath: $output);
        }
    }

    /**
     * Compile a single SCSS file to CSS.
     *
     * @param  string  $sourcePath  Path relative to the Kirby root (e.g. "assets/scss/main.scss")
     * @param  string  $outputPath  Path relative to the Kirby root (e.g. "assets/css/main.css")
     * @return bool True if the file was (re)compiled, false if skipped
     */
    public static function compile(string $sourcePath, string $outputPath): bool
    {
        $kirby = Kirby::instance();
        $sourceAbsolute = $kirby->root() . '/' . ltrim($sourcePath, '/');
        $outputAbsolute = $kirby->root() . '/' . ltrim($outputPath, '/');

        if (!file_exists($sourceAbsolute)) {
            return false;
        }

        $autoCompile = $kirby->option('rllngr.scss.autoCompile') ?? $kirby->option('debug', false);

        // In non-auto mode, only compile if the output file does not yet exist
        if (!$autoCompile && file_exists($outputAbsolute)) {
            return false;
        }

        // In auto mode, skip if nothing has changed
        if ($autoCompile && !static::needsCompilation($sourceAbsolute, $outputAbsolute)) {
            return false;
        }

        $compiler = static::buildCompiler($kirby, $sourceAbsolute);

        $scssSource = F::read($sourceAbsolute);
        $result = $compiler->compileString($scssSource, $sourceAbsolute);

        Dir::make(dirname($outputAbsolute));
        F::write($outputAbsolute, $result->getCss());

        // Persist the list of imported partials so change detection stays accurate
        static::saveIncludeCache($outputAbsolute, $result->getIncludedFiles());

        return true;
    }

    /**
     * Resolve the CSS path for a given page (template-based mode) and return
     * a ready-to-use <link> tag with a cache-busting version parameter.
     *
     * Falls back to the configured default template when the current page has
     * no dedicated SCSS file.
     */
    public static function cssTag(Page $page): string
    {
        $kirby    = Kirby::instance();
        $config   = $kirby->option('rllngr.scss.templates', []);
        $scssDir  = $config['scssDir'] ?? 'assets/scss';
        $cssDir   = $config['cssDir']  ?? 'assets/css';
        $default  = $config['default'] ?? 'default';

        $template = $page->template()->name();
        $scssPath = $scssDir . '/' . $template . '.scss';
        $cssPath  = $cssDir  . '/' . $template . '.css';

        // Fall back to the default template when no dedicated SCSS file exists
        if (!file_exists($kirby->root() . '/' . $scssPath)) {
            $scssPath = $scssDir . '/' . $default . '.scss';
            $cssPath  = $cssDir  . '/' . $default . '.css';
        }

        static::compile(sourcePath: $scssPath, outputPath: $cssPath);

        $outputAbsolute = $kirby->root() . '/' . $cssPath;
        $version        = file_exists($outputAbsolute) ? filemtime($outputAbsolute) : 0;

        return '<link rel="stylesheet" href="' . esc(url($cssPath), 'attr') . '?v=' . $version . '">';
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    protected static function buildCompiler(Kirby $kirby, string $sourceAbsolute): Compiler
    {
        $compiler = new Compiler();

        // Output style: honour explicit config, otherwise follow Kirby debug flag
        $outputStyle = $kirby->option('rllngr.scss.outputStyle') ?? match ($kirby->option('debug', false)) {
            true  => OutputStyle::EXPANDED,
            false => OutputStyle::COMPRESSED,
        };
        $compiler->setOutputStyle($outputStyle);

        // Import paths: source directory first, then any user-supplied extras
        $importPaths = [
            dirname($sourceAbsolute),
            ...$kirby->option('rllngr.scss.importPaths', []),
        ];
        $compiler->setImportPaths($importPaths);

        // Inject SCSS variables from config
        $variables = $kirby->option('rllngr.scss.variables', []);
        if (!empty($variables)) {
            $compiler->replaceVariables($variables);
        }

        return $compiler;
    }

    /**
     * Determine whether the source (or any of its partials) is newer than the output.
     */
    protected static function needsCompilation(string $source, string $output): bool
    {
        if (!file_exists($output)) {
            return true;
        }

        $outputMtime = filemtime($output);

        if (filemtime($source) > $outputMtime) {
            return true;
        }

        foreach (static::loadIncludeCache($output) as $partial) {
            if (file_exists($partial) && filemtime($partial) > $outputMtime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save the list of files that were imported during compilation alongside
     * the output file so they can be checked on the next request.
     *
     * @param  string[]  $includedFiles
     */
    protected static function saveIncludeCache(string $outputPath, array $includedFiles): void
    {
        $lines = array_filter($includedFiles, fn (string $f) => $f !== '');
        file_put_contents($outputPath . '.scss-deps', implode("\n", $lines));
    }

    /**
     * Load the previously saved list of imported partials.
     *
     * @return string[]
     */
    protected static function loadIncludeCache(string $outputPath): array
    {
        $cacheFile = $outputPath . '.scss-deps';

        if (!file_exists($cacheFile)) {
            return [];
        }

        return array_filter(explode("\n", file_get_contents($cacheFile)));
    }
}
