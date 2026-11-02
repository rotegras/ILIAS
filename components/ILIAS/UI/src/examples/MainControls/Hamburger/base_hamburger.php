<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\UI\examples\MainControls\Hamburger;

use ILIAS\DI\Container;
use ILIAS\UI\Factory;
use ILIAS\UI\Component\MainControls\MainBar;
use ILIAS\UI\Component\MainControls\MetaBar;

/**
 * ---
 * description: >
 *   Example for rendering a Hamburger in a Standard Page.
 *
 * expected output: >
 *   ILIAS shows a link "See UI in fullscreen-mode".
 *   On clicking the link, a new page opens. On large screens the page shows a
 *   Main Bar and a Meta Bar as usual.
 *   When the window is narrowed (or a mobile device is used), the Main Bar and the
 *   Meta Bar disappear and a button with a "hamburger" icon is shown in the header.
 *   The button shows a counter "3", the number of new notifications.
 *   Clicking the button opens a menu with the entries "Repository", "Communication",
 *   "Help" and the Meta Bar entries "Search", "Notifications" (with the counter "3")
 *   and "User".
 *   Clicking "Repository" or "User" expands a list of links; "Repository" contains a
 *   further expandable entry "Categories". Only one entry per level is expanded at a time.
 *   Clicking "Communication", "Help", "Search" or "Notifications" closes the menu and
 *   opens the respective slate with the content "some content", headed by a button
 *   "Back to Menu", which reopens the menu.
 *   Pressing Escape closes the menu.
 * ---
 */
function base_hamburger(): string
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $url = $DIC->http()->request()->getUri()->__toString() . '&new_hamburger_ui=1';
    $txt = $f->legacy()->content('<p>
            The Hamburger replaces the Main Bar and the Meta Bar on small screens.
            The example opens in fullscreen-mode; narrow the window to see the Hamburger.
            </p>');

    $page_demo = $f->link()->standard('See UI in fullscreen-mode', $url);

    return $renderer->render([
        $txt,
        $page_demo
    ]);
}

global $DIC;
$request_wrapper = $DIC->http()->wrapper()->query();
$refinery = $DIC->refinery();
if ($request_wrapper->has('new_hamburger_ui')
    && $request_wrapper->retrieve('new_hamburger_ui', $refinery->kindlyTo()->int()) === 1
) {
    \ilInitialisation::initILIAS();
    echo renderHamburgerInFullscreenMode($DIC);
    exit();
}

function renderHamburgerInFullscreenMode(Container $dic): string
{
    $f = $dic->ui()->factory();
    $renderer = $dic->ui()->renderer();

    $main_bar = buildHamburgerDemoMainBar($f);
    $meta_bar = buildHamburgerDemoMetaBar($f);

    $page = $f->layout()->page()->standard(
        [$f->panel()->standard('All about the Hamburger', $f->legacy()->content('Narrow the window to see the Hamburger.'))],
        $meta_bar,
        $main_bar,
        $f->breadcrumbs([]),
        $f->image()->responsive("assets/images/logo/HeaderIcon.svg", "ILIAS"),
        $f->image()->responsive("assets/images/logo/HeaderIconResponsive.svg", "ILIAS"),
        "./assets/images/logo/favicon.ico",
        $f->toast()->container(),
        $f->mainControls()->footer()->withAdditionalText('Footer'),
        'UI Hamburger DEMO',
        'ILIAS',
        'ILIAS Hamburger Demo'
    )
        ->withHamburger($f->mainControls()->hamburger($main_bar, $meta_bar))
        ->withUIDemo(true);

    return $renderer->render($page);
}

function buildHamburgerDemoMainBar(Factory $f): MainBar
{
    $icon = fn(string $abbr) => $f->symbol()->icon()->standard($abbr, $abbr)->withSize('medium');
    $link = fn(string $label) => $f->link()->bulky($icon('cat'), $label, new \ILIAS\Data\URI('https://www.ilias.de'));

    $repository = $f->mainControls()->slate()->combined('Repository', $icon('root'))
        ->withAdditionalEntry($link('Home'))
        ->withAdditionalEntry($link('Last Visited'))
        ->withAdditionalEntry(
            $f->mainControls()->slate()->combined('Categories', $icon('cat'))
                ->withAdditionalEntry($link('Category 1'))
                ->withAdditionalEntry($link('Category 2'))
        );

    $communication = $f->mainControls()->slate()->legacy(
        'Communication',
        $icon('mail'),
        $f->legacy()->content('some content')
    );

    $help = $f->mainControls()->slate()->legacy(
        'Help',
        $f->symbol()->glyph()->help(),
        $f->legacy()->content('some content')
    );

    return $f->mainControls()->mainBar()
        ->withAdditionalEntry('repository', $repository)
        ->withAdditionalEntry('communication', $communication)
        ->withToolsButton($f->button()->bulky($f->symbol()->glyph()->briefcase(), 'Tools', '#'))
        ->withAdditionalToolEntry('help', $help);
}

function buildHamburgerDemoMetaBar(Factory $f): MetaBar
{
    $search = $f->mainControls()->slate()->legacy(
        'Search',
        $f->symbol()->glyph()->search(),
        $f->legacy()->content('some content')
    );

    $notifications = $f->mainControls()->slate()->legacy(
        'Notifications',
        $f->symbol()->glyph()->notification()
            ->withCounter($f->counter()->novelty(3))
            ->withCounter($f->counter()->status(1)),
        $f->legacy()->content('some content')
    );

    $user = $f->mainControls()->slate()->combined('User', $f->symbol()->glyph()->user())
        ->withAdditionalEntry($f->link()->bulky($f->symbol()->glyph()->user(), 'Profile', new \ILIAS\Data\URI('https://www.ilias.de')))
        ->withAdditionalEntry($f->link()->bulky($f->symbol()->glyph()->logout(), 'Logout', new \ILIAS\Data\URI('https://www.ilias.de')));

    return $f->mainControls()->metaBar()
        ->withAdditionalEntry('search', $search)
        ->withAdditionalEntry('notifications', $notifications)
        ->withAdditionalEntry('user', $user);
}
