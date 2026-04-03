<?php
/**
 * Outputs the <link> tag for the current page's compiled CSS.
 * Triggers compilation if the source SCSS is newer than the output.
 *
 * Usage (from any template or snippet):
 *   <?php snippet('rllngr/scss') ?>
 */

use Rllngr\KirbyScss\ScssCompiler;

echo ScssCompiler::cssTag($page);
