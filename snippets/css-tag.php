<?php
/**
 * Outputs the <link> tag for the current page's compiled CSS.
 * Triggers compilation if the source SCSS is newer than the output.
 *
 * Usage (from any template or snippet):
 *   <?php snippet('rllngr/kirby-scssizer') ?>
 */

use Rllngr\SCSSizer\ScssCompiler;

echo ScssCompiler::cssTag($page);
