// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Template renderer for Moodle. Load and render Moodle templates with Mustache.
 *
 * @module     core/templates
 * @package    core
 * @class      templates
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import $ from 'jquery';
import Aria from './aria';
import Bootstrap from './bootstrap/index';
import Pending from 'core/pending';
import Scroll from './scroll';
import setupBootstrapPendingChecks from './pending';

/**
 * Rember the last visited tabs.
 */
const rememberTabs = () => {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var hash = $(e.target).attr('href');
        if (history.replaceState) {
            history.replaceState(null, null, hash);
        } else {
            location.hash = hash;
        }
    });
    var hash = window.location.hash;
    if (hash) {
       $('.nav-link[href="' + hash + '"]').tab('show');
    }
};

/**
 * Enable all popovers
 *
 */
const enablePopovers = () => {
    $('body').popover({
        container: 'body',
        selector: '[data-toggle="popover"]',
        trigger: 'focus',
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && e.target.closest('[data-toggle="popover"]')) {
            $(e.target).popover('hide');
        }
    });
};

/**
 * Enable tooltips
 *
 */
const enableTooltips = () => {
    $('body').tooltip({
        container: 'body',
        selector: '[data-toggle="tooltip"]',
    });
};

const pendingPromise = new Pending('theme_odl/loader:init');

// Add pending promise event listeners to relevant Bootstrap custom events.
setupBootstrapPendingChecks();

// Remember the last visited tabs.
rememberTabs();

// Enable all popovers.
enablePopovers();

// Enable all tooltips.
enableTooltips();

// Add scroll handling.
(new Scroll()).init();

// Disables flipping the dropdowns up and getting hidden behind the navbar.
$.fn.dropdown.Constructor.Default.flip = false;

// Setup Aria helpers for Bootstrap features.
Aria.init();

pendingPromise.resolve();

export {
    Bootstrap,
};
