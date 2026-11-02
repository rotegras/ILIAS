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

namespace ILIAS\UI\Component\MainControls;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\JavaScriptBindable;
use ILIAS\UI\Component\Signal;

/**
 * This describes the Hamburger, the navigation of the Standard Page on small screens.
 */
interface Hamburger extends Component, JavaScriptBindable
{
    /**
     * The Main Bar whose entries are presented in the Hamburger.
     */
    public function getMainBar(): MainBar;

    /**
     * The Meta Bar whose entries are presented in the Hamburger, if any.
     */
    public function getMetaBar(): ?MetaBar;

    /**
     * This signal opens the Hamburger if it is closed, and closes it otherwise.
     */
    public function getToggleSignal(): Signal;

    /**
     * This signal closes the Hamburger.
     */
    public function getCloseSignal(): Signal;
}
