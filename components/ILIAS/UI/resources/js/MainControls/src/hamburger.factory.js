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

import Hamburger from './hamburger.class';

export default class HamburgerFactory {
  /**
   * @type {jQuery}
   */
  #jquery;

  /**
   * @type {Object<string, Hamburger>}
   */
  #instances = {};

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
   * @param {function(): boolean} isSmallScreen
   * @param {function(): object|null} getMainbar
   * @param {function(): object|null} getMetabar
   * @param {function(string, string, string): void} replaceContent
   */
  constructor(jquery, isSmallScreen, getMainbar, getMetabar, replaceContent) {
    this.#jquery = jquery;
    this.#replaceContent = replaceContent;
    this.#isSmallScreen = isSmallScreen;
    this.#getMainbar = getMainbar;
    this.#getMetabar = getMetabar;
  }

  /**
   * @param {string} componentId
   * @param {string} toggleSignal
   * @param {string} closeSignal
   * @return {void}
   * @throws {Error} if the hamburger was already initialized.
   * Does nothing if the hamburger is not part of the page.
   */
  init(componentId, toggleSignal, closeSignal) {
    if (this.#instances[componentId] !== undefined) {
      throw new Error(`Hamburger with id '${componentId}' has already been initialized.`);
    }
    const element = document.getElementById(componentId);
    if (!element) {
      // e.g. a skin overriding tpl.standardpage.html without {HAMBURGER}
      return;
    }
    const hamburger = new Hamburger(
      this.#jquery,
      element,
      this.#isSmallScreen,
      this.#getMainbar,
      this.#getMetabar,
      this.#replaceContent,
    );
    hamburger.registerSignals(toggleSignal, closeSignal);
    this.#instances[componentId] = hamburger;
  }

  /**
   * @param {string} componentId
   * @return {Hamburger|null}
   */
  get(componentId) {
    return this.#instances[componentId] ?? null;
  }
}
