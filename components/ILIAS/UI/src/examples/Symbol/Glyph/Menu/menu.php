<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Glyph\Menu;

/**
 * ---
 * description: >
 *   Example for rendering a Menu Glyph.
 *
 * expected output: >
 *   Standard:
 *   ILIAS shows a monochrome "hamburger" symbol (three horizontal lines) on a grey background.
 *
 *   Highlighted:
 *   ILIAS shows the same symbol, but it's highlighted particularly.
 * ---
 */
function menu()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->menu();

    //Showcase the various states of this Glyph
    $list = $f->listing()->descriptive([
        "Active" => $glyph,
        "Highlighted" => $glyph->withHighlight()
    ]);

    return $renderer->render($list);
}
