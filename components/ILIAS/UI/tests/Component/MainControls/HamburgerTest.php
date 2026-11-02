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

require_once("vendor/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component as I;
use ILIAS\UI\Component\Signal;

/**
 * Tests for the Hamburger.
 */
class HamburgerTest extends ILIAS_UI_TestBase
{
    use LanguageStubs;

    protected I\SignalGenerator $sig_gen;
    protected I\Button\Factory $button_factory;
    protected I\Link\Factory $link_factory;
    protected I\Symbol\Icon\Factory $icon_factory;
    protected I\Symbol\Glyph\Factory $glyph_factory;
    protected I\Counter\Factory $counter_factory;
    protected I\MainControls\Slate\Factory $slate_factory;
    protected I\MainControls\Factory $factory;

    public function setUp(): void
    {
        $this->sig_gen = new I\SignalGenerator();
        $this->button_factory = new I\Button\Factory();
        $this->link_factory = new I\Link\Factory();
        $this->icon_factory = new I\Symbol\Icon\Factory();
        $this->glyph_factory = new I\Symbol\Glyph\Factory($this->createRelayArgumentLanguageStub());
        $this->counter_factory = new I\Counter\Factory();
        $this->slate_factory = new I\MainControls\Slate\Factory(
            $this->sig_gen,
            $this->counter_factory,
            new I\Symbol\Factory($this->icon_factory, $this->glyph_factory, new I\Symbol\Avatar\Factory())
        );
        $this->factory = new I\MainControls\Factory($this->sig_gen, $this->slate_factory);
    }

    public function getUIFactory(): NoUIFactory
    {
        $factory = new class ($this->createRelayArgumentLanguageStub()) extends NoUIFactory {
            public I\Button\Factory $button_factory;
            public I\Counter\Factory $counter_factory;
            public I\MainControls\Factory $mc_factory;

            public function __construct(
                protected \ILIAS\Language\Language $language,
            ) {
            }

            public function button(): I\Button\Factory
            {
                return $this->button_factory;
            }
            public function counter(): I\Counter\Factory
            {
                return $this->counter_factory;
            }
            public function mainControls(): I\MainControls\Factory
            {
                return $this->mc_factory;
            }
            public function symbol(): I\Symbol\Factory
            {
                return new I\Symbol\Factory(
                    new I\Symbol\Icon\Factory(),
                    new I\Symbol\Glyph\Factory($this->language),
                    new I\Symbol\Avatar\Factory()
                );
            }
        };
        $factory->button_factory = $this->button_factory;
        $factory->counter_factory = $this->counter_factory;
        $factory->mc_factory = $this->factory;
        return $factory;
    }

    protected function getIcon(): C\Symbol\Icon\Icon
    {
        return $this->icon_factory->custom('', '');
    }

    protected function getBulkyLink(string $label): C\Link\Bulky
    {
        return $this->link_factory->bulky(
            $this->getIcon(),
            $label,
            new \ILIAS\Data\URI('http://www.ilias.de/' . $label)
        );
    }

    protected function getLegacySlate(string $name, C\Symbol\Symbol $symbol): C\MainControls\Slate\Legacy
    {
        return $this->slate_factory->legacy($name, $symbol, new I\Legacy\Content('content', $this->sig_gen));
    }

    protected function getHamburgerHTML(C\MainControls\Hamburger $hamburger): string
    {
        return $this->getDefaultRenderer()->render($hamburger);
    }

    public function testConstruction(): void
    {
        $main_bar = $this->factory->mainBar();
        $meta_bar = $this->factory->metaBar();
        $hamburger = $this->factory->hamburger($main_bar, $meta_bar);

        $this->assertInstanceOf(C\MainControls\Hamburger::class, $hamburger);
        $this->assertSame($main_bar, $hamburger->getMainBar());
        $this->assertSame($meta_bar, $hamburger->getMetaBar());
    }

    public function testConstructionWithoutMetaBar(): void
    {
        $hamburger = $this->factory->hamburger($this->factory->mainBar());
        $this->assertNull($hamburger->getMetaBar());
    }

    public function testSignals(): void
    {
        $hamburger = $this->factory->hamburger($this->factory->mainBar());
        $this->assertInstanceOf(Signal::class, $hamburger->getToggleSignal());
        $this->assertInstanceOf(Signal::class, $hamburger->getCloseSignal());
        $this->assertNotEquals((string) $hamburger->getToggleSignal(), (string) $hamburger->getCloseSignal());
    }

    public function testRenderToggleAndPanel(): void
    {
        $html = $this->getHamburgerHTML($this->factory->hamburger($this->factory->mainBar()));
        $dom = $this->getDOM($html);
        $xpath = new DOMXPath($dom);

        $root = $xpath->query('//div[contains(@class, "il-hamburger")]')->item(0);
        $id = $root->getAttribute('id');
        $this->assertNotEmpty($id);

        $toggle = $xpath->query('//button[@class="il-hamburger-toggle"]')->item(0);
        $this->assertEquals('false', $toggle->getAttribute('aria-expanded'));
        $this->assertEquals($id . '_panel', $toggle->getAttribute('aria-controls'));
        $this->assertEquals('hamburger_open', $toggle->getAttribute('aria-label'));
        $this->assertEquals(1, $xpath->query('//span[contains(@class, "glyphicon-menu")]')->length);

        $panel = $xpath->query('//nav[@class="il-hamburger-panel"]')->item(0);
        $this->assertEquals($id . '_panel', $panel->getAttribute('id'));
        $this->assertEquals('hamburger_aria_label', $panel->getAttribute('aria-label'));
        // no metabar given, no metabar section
        $this->assertEquals(0, $xpath->query('//ul[contains(@class, "il-hamburger-metabar-entries")]')->length);
    }

    public function testRenderCombinedSlateAsSection(): void
    {
        $slate = $this->slate_factory->combined('Section', $this->getIcon())
            ->withAdditionalEntry($this->getBulkyLink('first'))
            ->withAdditionalEntry(
                $this->slate_factory->combined('Nested', $this->getIcon())
                    ->withAdditionalEntry($this->getBulkyLink('second'))
            );
        $main_bar = $this->factory->mainBar()->withAdditionalEntry('section', $slate);

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($this->factory->hamburger($main_bar))));

        $triggers = $xpath->query('//button[contains(@class, "il-hamburger-section-trigger")]');
        $this->assertEquals(2, $triggers->length);
        foreach ($triggers as $trigger) {
            $this->assertEquals('false', $trigger->getAttribute('aria-expanded'));
            $sub = $xpath->query('//div[@id="' . $trigger->getAttribute('aria-controls') . '"]');
            $this->assertEquals(1, $sub->length);
        }
        $this->assertEquals(2, $xpath->query('//div[contains(@class, "il-hamburger-sub")]//a[contains(@class, "link-bulky")]')->length);
        $this->assertEquals(1, $xpath->query('//div[contains(@class, "il-hamburger-sub")]//div[contains(@class, "il-hamburger-sub")]//a[contains(@class, "link-bulky")]')->length);
    }

    public function testRenderLegacySlateInline(): void
    {
        $main_bar = $this->factory->mainBar()
            ->withAdditionalEntry('link', $this->getBulkyLink('link'))
            ->withAdditionalEntry('legacy', $this->getLegacySlate('Legacy', $this->getIcon()));

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($this->factory->hamburger($main_bar))));

        $this->assertEquals(1, $xpath->query('//li[contains(@class, "il-hamburger-plain")]/a[contains(@class, "link-bulky")]')->length);
        $trigger = $xpath->query('//button[contains(@class, "il-hamburger-section-trigger")]')->item(0);
        $this->assertStringContainsString('Legacy', $trigger->textContent);
        $content = $xpath->query('//div[@id="' . $trigger->getAttribute('aria-controls') . '"]/div[@class="il-hamburger-content"]')->item(0);
        $this->assertEquals('content', trim($content->textContent));
    }

    public function testRenderAsyncSlateLoadsIntoSection(): void
    {
        $slate = $this->slate_factory->combined('Async', $this->getIcon())
            ->withAdditionalEntry($this->getBulkyLink('first'));
        $slate = $slate->appendOnInView($slate->getReplaceSignal()->withAsyncRenderUrl('async.php?item=1'));
        $main_bar = $this->factory->mainBar()->withAdditionalEntry('async', $slate);

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($this->factory->hamburger($main_bar))));

        $sub = $xpath->query('//div[@data-il-hamburger-async-url]')->item(0);
        $this->assertEquals('async.php?item=1', $sub->getAttribute('data-il-hamburger-async-url'));
        // content is loaded on expand, nothing rendered upfront
        $this->assertEquals(0, $xpath->query('.//a', $sub)->length);
        $this->assertEquals(1, $xpath->query('.//div[@data-replace-marker="content"]', $sub)->length);
        // the replace signal is handled by the hamburger itself
        $trigger = $xpath->query('//button[contains(@class, "il-hamburger-section-trigger")]')->item(0);
        $this->assertFalse($trigger->hasAttribute('data-il-hamburger-in-view'));
    }

    public function testRenderInViewSignals(): void
    {
        $signal = $this->sig_gen->create();
        $slate = $this->slate_factory->combined('Section', $this->getIcon())
            ->withAdditionalEntry($this->getBulkyLink('first'))
            ->appendOnInView($signal);
        $main_bar = $this->factory->mainBar()->withAdditionalEntry('section', $slate);

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($this->factory->hamburger($main_bar))));

        $trigger = $xpath->query('//button[contains(@class, "il-hamburger-section-trigger")]')->item(0);
        $this->assertEquals([$signal->getId()], json_decode($trigger->getAttribute('data-il-hamburger-in-view'), true));
    }

    public function testRenderEmptyCombinedSlateIsOmitted(): void
    {
        $main_bar = $this->factory->mainBar()
            ->withAdditionalEntry('empty', $this->slate_factory->combined('Empty', $this->getIcon()));

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($this->factory->hamburger($main_bar))));

        $this->assertEquals(0, $xpath->query('//li[contains(@class, "il-hamburger-entry")]')->length);
    }

    public function testRenderTools(): void
    {
        $tool = $this->getLegacySlate('Tool', $this->getIcon());
        $hidden_tool = $this->getLegacySlate('Hidden', $this->getIcon());
        $main_bar = $this->factory->mainBar()
            ->withToolsButton($this->button_factory->bulky($this->getIcon(), 'Tools', '#'))
            ->withAdditionalToolEntry('tool', $tool)
            ->withAdditionalToolEntry('hidden', $hidden_tool, true);

        $html = $this->getHamburgerHTML($this->factory->hamburger($main_bar));
        $xpath = new DOMXPath($this->getDOM($html));

        $tools = $xpath->query('//li[contains(@class, "il-hamburger-tool")]/button');
        $this->assertEquals(1, $tools->length);
        $this->assertStringContainsString('Tool', $tools->item(0)->textContent);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function testRenderMetaBarEntriesAndCounters(): void
    {
        $notification_symbol = $this->glyph_factory->notification()
            ->withCounter($this->counter_factory->novelty(3))
            ->withCounter($this->counter_factory->status(2));
        $user = $this->slate_factory->combined('User', $this->getIcon())
            ->withAdditionalEntry($this->getBulkyLink('logout'));

        $meta_bar = $this->factory->metaBar()
            ->withAdditionalEntry('notifications', $this->getLegacySlate('Notifications', $notification_symbol))
            ->withAdditionalEntry('user', $user);
        $hamburger = $this->factory->hamburger($this->factory->mainBar(), $meta_bar);

        $xpath = new DOMXPath($this->getDOM($this->getHamburgerHTML($hamburger)));
        $metabar = '//ul[contains(@class, "il-hamburger-metabar-entries")]';

        // metabar entries are linked to their position in the metabar to mirror counters
        $triggers = $xpath->query($metabar . '/li/button[contains(@class, "il-hamburger-section-trigger")]');
        $this->assertEquals(2, $triggers->length);
        $this->assertEquals('0', $triggers->item(0)->getAttribute('data-il-hamburger-metabar-index'));
        $this->assertEquals('1', $triggers->item(1)->getAttribute('data-il-hamburger-metabar-index'));
        $this->assertEquals('3', trim($xpath->query('.//span[contains(@class, "il-counter-novelty")]', $triggers->item(0))->item(0)->textContent));

        // counters of the metabar are summed up on the toggle
        $toggle = '//button[@class="il-hamburger-toggle"]';
        $this->assertEquals('3', trim($xpath->query($toggle . '//span[contains(@class, "il-counter-novelty")]')->item(0)->textContent));
        $this->assertEquals('2', trim($xpath->query($toggle . '//span[contains(@class, "il-counter-status")]')->item(0)->textContent));
    }

    public function testRenderNotificationSlates(): void
    {
        $center = $this->slate_factory->combined('Notification Center', $this->glyph_factory->notification())
            ->withAdditionalEntry($this->slate_factory->notification('Group', [$this->getBulkyLink('item')]));
        $meta_bar = $this->factory->metaBar()->withAdditionalEntry('noc', $center);

        $html = $this->getHamburgerHTML($this->factory->hamburger($this->factory->mainBar(), $meta_bar));
        $xpath = new DOMXPath($this->getDOM($html));

        $labels = array_map(
            static fn($node) => trim($node->textContent),
            iterator_to_array($xpath->query('//button[contains(@class, "il-hamburger-section-trigger")]/span[@class="il-hamburger-label"]'))
        );
        $this->assertEquals(['Notification Center', 'Group'], $labels);
        $this->assertEquals(1, $xpath->query('//div[contains(@class, "il-hamburger-sub")]//div[contains(@class, "il-hamburger-sub")]//a[contains(@class, "link-bulky")]')->length);
    }

    public function testRenderTriggersOnLoadCode(): void
    {
        $hamburger = $this->factory->hamburger($this->factory->mainBar());
        $html = $this->getHamburgerHTML($hamburger);
        $this->assertStringContainsString('class="il-hamburger"', $html);
        $this->assertMatchesRegularExpression('/<div class="il-hamburger" id="id_\d+">/', $html);
    }

    protected function getDOM(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>');
        libxml_clear_errors();
        return $dom;
    }
}
