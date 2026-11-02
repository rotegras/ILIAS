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

namespace ILIAS\UI\Implementation\Component\MainControls;

use ILIAS\UI\Component\MainControls;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

class Hamburger implements MainControls\Hamburger
{
    use ComponentHelper;
    use JavaScriptBindable;

    protected Signal $toggle_signal;
    protected Signal $close_signal;

    public function __construct(
        protected SignalGeneratorInterface $signal_generator,
        protected MainControls\MainBar $main_bar,
        protected ?MainControls\MetaBar $meta_bar = null
    ) {
        $this->initSignals();
    }

    public function getMainBar(): MainControls\MainBar
    {
        return $this->main_bar;
    }

    public function getMetaBar(): ?MainControls\MetaBar
    {
        return $this->meta_bar;
    }

    public function getToggleSignal(): Signal
    {
        return $this->toggle_signal;
    }

    public function getCloseSignal(): Signal
    {
        return $this->close_signal;
    }

    protected function initSignals(): void
    {
        $this->toggle_signal = $this->signal_generator->create();
        $this->close_signal = $this->signal_generator->create();
    }
}
