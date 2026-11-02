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
 */

const classEngaged = 'engaged';
const classPageEngaged = 'with-hamburger-engaged';
const classPageMainbarEngaged = 'with-mainbar-slates-engaged';

const selectorPage = '.il-layout-page';
const selectorToggle = '.il-hamburger-toggle';
const selectorPanel = '.il-hamburger-panel';
const selectorBack = '.il-hamburger-back-button';
const selectorSectionTrigger = '.il-hamburger-section-trigger';
const selectorMetabar = '.il-maincontrols-metabar';
const selectorMetabarEntries = '.il-hamburger-metabar-entries';
const selectorFocusable = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

const attributeAsyncUrl = 'data-il-hamburger-async-url';

export default class Hamburger {
  /**
   * @type {jQuery}
   */
  #jquery;

  /**
   * @type {HTMLElement}
   */
  #element;

  /**
   * @type {HTMLButtonElement}
   */
  #toggle;

  /**
   * @type {HTMLElement}
   */
  #panel;

  /**
   * @type {function(): boolean}
   */
  #isSmallScreen;

  /**
   * @type {function(): object|null}
   */
  #getMainbar;

  /**
   * @type {function(): object|null}
   */
  #getMetabar;

  /**
   * @type {function(string, string, string): void}
   */
  #replaceContent;

  /**
   * @param {jQuery} jquery
   * @param {HTMLElement} element
   * @param {function(): boolean} isSmallScreen
   * @param {function(): object|null} getMainbar
   * @param {function(): object|null} getMetabar
   * @param {function(string, string, string): void} replaceContent
   */
  constructor(jquery, element, isSmallScreen, getMainbar, getMetabar, replaceContent) {
    this.#jquery = jquery;
    this.#replaceContent = replaceContent;
    this.#element = element;
    this.#toggle = element.querySelector(selectorToggle);
    this.#panel = element.querySelector(selectorPanel);
    this.#isSmallScreen = isSmallScreen;
    this.#getMainbar = getMainbar;
    this.#getMetabar = getMetabar;

    this.#registerEvents();
    this.#observeMainbar();
    this.#observeMetabar();
  }

  /**
   * @param {string} toggleSignal
   * @param {string} closeSignal
   * @return {void}
   */
  registerSignals(toggleSignal, closeSignal) {
    this.#jquery(document).on(toggleSignal, () => {
      this.toggle();
      return false;
    });
    this.#jquery(document).on(closeSignal, () => {
      this.close();
      return false;
    });
  }

  /**
   * @return {boolean}
   */
  isOpen() {
    return this.#element.classList.contains(classEngaged);
  }

  /**
   * @return {void}
   */
  toggle() {
    if (this.isOpen()) {
      this.close();
    } else {
      this.open();
    }
  }

  /**
   * @return {void}
   */
  open() {
    this.#disengageOriginals();
    this.#element.classList.add(classEngaged);
    this.#getPage()?.classList.add(classPageEngaged);
    this.#toggle.setAttribute('aria-expanded', 'true');
    this.#toggle.setAttribute('aria-label', this.#toggle.dataset.labelClose);
    this.#getFocusables()[0]?.focus();
  }

  /**
   * @param {boolean} returnFocus
   * @return {void}
   */
  close(returnFocus = true) {
    if (!this.isOpen()) {
      return;
    }
    this.#element.classList.remove(classEngaged);
    this.#getPage()?.classList.remove(classPageEngaged);
    this.#toggle.setAttribute('aria-expanded', 'false');
    this.#toggle.setAttribute('aria-label', this.#toggle.dataset.labelOpen);
    if (returnFocus) {
      this.#toggle.focus();
    }
  }

  #registerEvents() {
    this.#toggle.addEventListener('click', (event) => {
      // prevent the metabar from closing slates on body-click right after opening
      event.stopPropagation();
      this.toggle();
    });

    this.#element.querySelector(selectorBack)?.addEventListener('click', (event) => {
      event.stopPropagation();
      this.open();
    });

    this.#panel.addEventListener('click', (event) => {
      const sectionTrigger = event.target.closest(selectorSectionTrigger);
      if (sectionTrigger) {
        event.stopPropagation();
        this.#toggleSection(sectionTrigger);
      }
    });

    this.#element.addEventListener('keydown', (event) => this.#onKeyDown(event));

    window.addEventListener('resize', () => {
      if (!this.#isSmallScreen()) {
        this.close(false);
      }
    });
  }

  /**
   * Only one section per level is expanded at a time.
   * @param {HTMLButtonElement} trigger
   */
  #toggleSection(trigger) {
    const expand = trigger.getAttribute('aria-expanded') !== 'true';
    const list = trigger.closest('ul');
    list.querySelectorAll(`:scope > li > ${selectorSectionTrigger}[aria-expanded="true"]`)
      .forEach((other) => this.#setSectionExpanded(other, false));
    this.#setSectionExpanded(trigger, expand);
    if (expand) {
      this.#onSectionInView(trigger);
    }
  }

  /**
   * Load asynchronous contents (once) and trigger the in_view-signals of the slate.
   * @param {HTMLButtonElement} trigger
   */
  #onSectionInView(trigger) {
    const sub = document.getElementById(trigger.getAttribute('aria-controls'));
    if (sub?.hasAttribute(attributeAsyncUrl)) {
      const url = sub.getAttribute(attributeAsyncUrl);
      sub.removeAttribute(attributeAsyncUrl);
      this.#replaceContent(sub.id, url, 'content');
    }
    const signals = JSON.parse(trigger.dataset.ilHamburgerInView ?? '[]');
    signals.forEach((signal) => {
      this.#jquery(document).trigger(signal, {
        id: signal,
        event: 'in_view',
        triggerer: this.#jquery(trigger),
        options: {},
      });
    });
  }

  /**
   * @param {HTMLButtonElement} trigger
   * @param {boolean} expanded
   */
  #setSectionExpanded(trigger, expanded) {
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    trigger.classList.toggle(classEngaged, expanded);
    if (!expanded) {
      document.getElementById(trigger.getAttribute('aria-controls'))
        ?.querySelectorAll(`${selectorSectionTrigger}[aria-expanded="true"]`)
        .forEach((nested) => this.#setSectionExpanded(nested, false));
    }
  }

  #disengageOriginals() {
    const mainbar = this.#getMainbar();
    if (mainbar && this.#getPage()?.classList.contains(classPageMainbarEngaged)) {
      mainbar.disengageAll();
    }
    this.#getMetabar()?.disengageAll();
  }

  /**
   * @param {KeyboardEvent} event
   */
  #onKeyDown(event) {
    if (event.key === 'Escape' && this.isOpen()) {
      event.preventDefault();
      this.close();
      return;
    }
    if (event.key !== 'Tab' || !this.isOpen()) {
      return;
    }
    // keep the focus within the toggle and the panel while open
    const focusables = [this.#toggle, ...this.#getFocusables()];
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  /**
   * @return {HTMLElement[]}
   */
  #getFocusables() {
    return [...this.#panel.querySelectorAll(selectorFocusable)]
      .filter((element) => element.offsetParent !== null);
  }

  /**
   * @return {HTMLElement|null}
   */
  #getPage() {
    return this.#element.closest(selectorPage);
  }

  /**
   * @return {HTMLElement|null}
   */
  #getMetabarElement() {
    return document.querySelector(selectorMetabar);
  }

  /**
   * @param {string} index
   * @return {HTMLElement|null}
   */
  #getMetabarSourceButton(index) {
    const entry = this.#getMetabarElement()?.querySelectorAll(':scope > li')[Number(index)];
    return entry?.querySelector(':scope > .btn, :scope > .il-link') ?? null;
  }

  /**
   * Slates of the Main Bar may be engaged without the Hamburger (e.g. tools,
   * triggered by the help); close the Hamburger in this case.
   */
  #observeMainbar() {
    const page = this.#getPage();
    if (!page) {
      return;
    }
    new MutationObserver(() => {
      if (page.classList.contains(classPageMainbarEngaged)) {
        this.close(false);
      }
    }).observe(page, { attributes: true, attributeFilter: ['class'] });
  }

  /**
   * Counters of the Meta Bar (e.g. notifications) change after rendering;
   * mirror them to the Hamburger and its toggle.
   */
  #observeMetabar() {
    const metabar = this.#getMetabarElement();
    if (!metabar) {
      return;
    }
    let scheduled = false;
    new MutationObserver(() => {
      if (scheduled) {
        return;
      }
      scheduled = true;
      window.requestAnimationFrame(() => {
        scheduled = false;
        this.#syncCounters();
      });
    }).observe(metabar, {
      subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['style'],
    });
  }

  #syncCounters() {
    this.#panel.querySelectorAll(`${selectorMetabarEntries} > li > [data-il-hamburger-metabar-index]`)
      .forEach((trigger) => {
        const sourceGlyph = this.#getMetabarSourceButton(trigger.dataset.ilHamburgerMetabarIndex)
          ?.querySelector('.glyph');
        const glyph = trigger.querySelector(':scope > .glyph');
        if (sourceGlyph && glyph) {
          glyph.replaceWith(sourceGlyph.cloneNode(true));
        }
      });

    const sum = (type) => [...this.#panel.querySelectorAll(
      `${selectorMetabarEntries} > li > :is(button, a) .il-counter-${type}`,
    )].filter((counter) => counter.style.display !== 'none')
      .reduce((total, counter) => total + (parseInt(counter.textContent, 10) || 0), 0);

    [['novelty', sum('novelty')], ['status', sum('status')]].forEach(([type, value]) => {
      const counter = this.#toggle.querySelector(`.il-counter-${type}`);
      if (counter) {
        counter.textContent = String(value);
        counter.style.display = value === 0 ? 'none' : '';
      }
    });
  }
}
