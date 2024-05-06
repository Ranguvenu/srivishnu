<?php
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
 * odl core_renderer
 *
 * @package   theme_odl
 * @copyright 2018 eAbyas Info Solutons Pvt Ltd, India
 * @author    eAbyas  <info@eAbyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_odl\output;

use coding_exception;
use html_writer;
use tabobject;
use tabtree;
use custom_menu_item;
use custom_menu;
use block_contents;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use preferences_groups;
use action_menu;
use help_icon;
use single_button;
use paging_bar;
use context_course;
use pix_icon;

use context_system;
use context_user;
use context_coursecat;
use core_component;
use action_menu_filler;
use action_menu_link_secondary;
use core_text;
use user_picture;
use costcenter;

defined('MOODLE_INTERNAL') || die;

class core_renderer extends \core_renderer {

    /** @var custom_menu_item language The language menu if created */
    protected $language = null;

    /**
     * We don't like these...
     *
     */
    
    public function edit_button(moodle_url $url) {
        $url->param('sesskey', sesskey());
        if ($this->page->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('turneditingon');
        }
        $button = new \single_button($url, $editstring, 'post', ['class' => 'btn btn-primary']);
        return $this->render_single_button($button);
    }

    /**
     * Outputs the opening section of a box.
     *
     * @param string $classes A space-separated list of CSS classes
     * @param string $id An optional ID
     * @param array $attributes An array of other attributes to give the box.
     * @return string the HTML to output.
     */
    public function box_start($classes = 'generalbox', $id = null, $attributes = array()) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }
        return parent::box_start($classes . ' p-y-1', $id, $attributes);
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        global $PAGE;

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'HIROw row'));
        $html .= html_writer::start_div('col-12');
        $html .= html_writer::start_div('card');
        $html .= html_writer::start_div('card-block');

        $showextendedmenu = '';
        $context = $this->page->context;
        $courseid = $this->page->course->id;
        $pagetype = $this->page->pagetype;

        $course_extended_menu = '';

        if (($context->contextlevel == CONTEXT_COURSE) && $courseid > 1) {
            $course_extended_menu = $this->course_context_header_settings_menu();
        }else{
            $course_extended_menu = $this->context_header_settings_menu();
        }
        $html .= html_writer::start_div('pull-left');
        $html .= $this->context_header('', 2);
        $html .= html_writer::end_div();
        $pageheadingbutton = $this->page_heading_button();
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix pull-right', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button pull-right');
            $html .= html_writer::end_div();
        } else if ($pageheadingbutton) {
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button nonavbar pull-right');
        }
        $html .= html_writer::div($course_extended_menu, 'pull-right context-header-settings-menu');
        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * The standard tags that should be included in the <head> tag
     * including a meta description for the front page
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $SITE, $PAGE;

        $output = parent::standard_head_html();
        if ($PAGE->pagelayout == 'frontpage') {
            $summary = s(strip_tags(format_text($SITE->summary, FORMAT_HTML)));
            if (!empty($summary)) {
                $output .= "<meta name=\"description\" content=\"$summary\" />\n";
            }
        }

        return $output;
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    //public function navbar() {
    //    return $this->render_from_template('core/navbar', $this->page->navbar);
    //}
    /*bug-929 fix*/
    public function navbar() {
        $showcategories = true;
        if (($this->page->pagelayout == 'course') || ($this->page->pagelayout == 'incourse')) {
            $showcategories = 1;
        }
        $breadcrumbs = html_writer::start_tag('nav', array('role' => 'navigation'));
        $breadcrumbs .= html_writer::start_tag('ol', array('class' => "breadcrumb"));
        foreach ($this->page->navbar->get_items() as $item) {
            if ((strlen($item->text) == 1) && ($item->text[0] == ' ')) {
                continue;
            }
            if ((!$showcategories) && ($item->type == navigation_node::TYPE_CATEGORY)) {
                continue;
            }
            if ($item->text == 'Courses') {
                continue;
            }
            $item->hideicon = true;
            $breadcrumbs .= html_writer::tag('li', $this->render($item), array('class' => "breadcrumb-item"));
        }
        $breadcrumbs .= html_writer::end_tag('ul');
        $breadcrumbs .= html_writer::end_tag('nav');
        return $breadcrumbs;
    }
    
    /**
     * Override to inject the logo.
     *
     * @param array $headerinfo The header info.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {
        global $SITE;

        if ($this->should_display_main_logo($headinglevel)) {
            $sitename = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));
            return html_writer::div(html_writer::empty_tag('img', [
                'src' => $this->get_custom_logo(null, 150), 'alt' => $sitename]), 'logo');
        }

        return parent::context_header($headerinfo, $headinglevel);
    }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        return parent::get_compact_logo_url(null, 70);
    }

    /**
     * Whether we should display the main logo.
     *
     * @return bool
     */
    public function should_display_main_logo($headinglevel = 1) {
        global $PAGE;

        // Only render the logo if we're on the front page or login page and the we have a logo.
        $logo = $this->get_custom_logo();
        if($headinglevel == 1 && !empty($logo)){
            return true;
        }
        //commented by Raghuvaran to remove the compact logo
        //if ($headinglevel == 1 && !empty($logo)) {
        //    if ($PAGE->pagelayout == 'frontpage' || $PAGE->pagelayout == 'login') {
        //        return true;
        //    }
        //}

        return false;
    }

    /**
     * Whether we should display the logo in the navbar.
     *
     * We will when there are no main logos, and we have compact logo.
     *
     * @return bool
     */
    public function should_display_navbar_logo() {
        $logo = $this->get_custom_logo();//$this->get_compact_logo_url();
        return !empty($logo) && !$this->should_display_main_logo();
    }

    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    /**
     * We want to show the custom menus as a list of links in the footer on small screens.
     * Just return the menu object exported so we can render it differently.
     */
    public function custom_menu_flat() {
        global $CFG;
        $custommenuitems = '';

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $custommenu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        return $custommenu->export_for_template($this);
    }

    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG;

        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if (!$menu->has_children() && !$haslangmenu) {
            return '';
        }

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
    }

    /**
     * This code renders the navbar button to control the display of the custom menu
     * on smaller screens.
     *
     * Do not display the button if the menu is empty.
     *
     * @return string HTML fragment
     */
    public function navbar_button() {
        global $CFG;

        if (empty($CFG->custommenuitems) && $this->lang_menu() == '') {
            return '';
        }

        $iconbar = html_writer::tag('span', '', array('class' => 'icon-bar'));
        $button = html_writer::tag('a', $iconbar . "\n" . $iconbar. "\n" . $iconbar, array(
            'class'       => 'btn btn-navbar',
            'data-toggle' => 'collapse',
            'data-target' => '.nav-collapse'
        ));
        return $button;
    }

    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $data = $tabtree->export_for_template($this);
        return $this->render_from_template('core/tabtree', $data);
    }

    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tab) {
        throw new coding_exception('Tab objects should not be directly rendered.');
    }

    /**
     * Prints a nice side block with an optional header.
     *
     * @param block_contents $bc HTML for the content
     * @param string $region the region the block is appearing in.
     * @return string the HTML to be output.
     */
    public function block(block_contents $bc, $region) {
        $bc = clone($bc); // Avoid messing up the object passed in.
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }

        $id = !empty($bc->attributes['id']) ? $bc->attributes['id'] : uniqid('block-');
        $context = new stdClass();
        $context->skipid = $bc->skipid;
        $context->blockinstanceid = $bc->blockinstanceid;
        $context->dockable = $bc->dockable;
        $context->id = $id;
        $context->hidden = $bc->collapsible == block_contents::HIDDEN;
        $context->skiptitle = strip_tags($bc->title);
        $context->showskiplink = !empty($context->skiptitle);
        $context->arialabel = $bc->arialabel;
        $context->ariarole = !empty($bc->attributes['role']) ? $bc->attributes['role'] : 'complementary';
        $context->type = $bc->attributes['data-block'];
        $context->class = 'block block_' . $bc->attributes['data-block'];
        $context->title = $bc->title;
        $context->content = $bc->content;
        $context->annotation = $bc->annotation;
        $context->footer = $bc->footer;
        $context->hascontrols = !empty($bc->controls);
        if ($context->hascontrols) {
            $context->controls = $this->block_controls($bc->controls, $id);
        }

        return $this->render_from_template('core/block', $context);
    }

    /**
     * Returns the CSS classes to apply to the body tag.
     *
     * @since Moodle 2.5.1 2.6
     * @param array $additionalclasses Any additional classes to apply.
     * @return string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        return $this->page->bodyclasses . ' ' . implode(' ', $additionalclasses);
    }

    /**
     * Renders preferences groups.
     *
     * @param  preferences_groups $renderable The renderable
     * @return string The output.
     */
    public function render_preferences_groups(preferences_groups $renderable) {
        return $this->render_from_template('core/preferences_groups', $renderable);
    }

    /**
     * Renders an action menu component.
     *
     * @param action_menu $menu
     * @return string HTML
     */
    public function render_action_menu(action_menu $menu) {

        // We don't want the class icon there!
        foreach ($menu->get_secondary_actions() as $action) {
            if ($action instanceof \action_menu_link && $action->has_class('icon')) {
                $action->attributes['class'] = preg_replace('/(^|\s+)icon(\s+|$)/i', '', $action->attributes['class']);
            }
        }

        if ($menu->is_empty()) {
            return '';
        }
        $context = $menu->export_for_template($this);

        return $this->render_from_template('core/action_menu', $context);
    }

    /**
     * Implementation of user image rendering.
     *
     * @param help_icon $helpicon A help icon instance
     * @return string HTML fragment
     */
    protected function render_help_icon(help_icon $helpicon) {
        $context = $helpicon->export_for_template($this);
        return $this->render_from_template('core/help_icon', $context);
    }

    /**
     * Renders a single button widget.
     *
     * This will return HTML to display a form containing a single button.
     *
     * @param single_button $button
     * @return string HTML fragment
     */
    protected function render_single_button(single_button $button) {
        return $this->render_from_template('core/single_button', $button->export_for_template($this));
    }

    /**
     * Renders a paging bar.
     *
     * @param paging_bar $pagingbar The object.
     * @return string HTML
     */
    protected function render_paging_bar(paging_bar $pagingbar) {
        // Any more than 10 is not usable and causes wierd wrapping of the pagination in this theme.
        $pagingbar->maxdisplay = 10;
        return $this->render_from_template('core/paging_bar', $pagingbar->export_for_template($this));
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $SITE, $OUTPUT;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);
        $context->output = $OUTPUT;
        $helptext = $this->page->theme->settings->helpdesc;
        $contactustext = $this->page->theme->settings->contact;
        $aboutustext = $this->page->theme->settings->aboutus;
        if(!empty($helptext)||(!empty($contactustext))||(!empty($aboutustext))){
            $context->helptext = $helptext;
            $context->contactustext = $contactustext;
            $context->aboutustext = $aboutustext;
        }else{
            $context->helptext = '';
            $context->contactustext = '';
            $context->aboutustext = '';
        }
        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param mform $form
     * @return string
     */
    public function render_login_signup_form($form) {
        global $SITE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('core/signup_form_layout', $context);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the course administration, only on the course main page.
     *
     * @return string
     */
    public function context_header_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();

        $items = $this->page->navbar->get_items();
        $currentnode = end($items);

        $showcoursemenu = false;
        $showfrontpagemenu = false;
        $showusermenu = false;

        // We are on the course home page.
        if (($context->contextlevel == CONTEXT_COURSE) &&
                !empty($currentnode) &&
                ($currentnode->type == navigation_node::TYPE_COURSE || $currentnode->type == navigation_node::TYPE_SECTION)) {
            $showcoursemenu = true;
        }

        $courseformat = course_get_format($this->page->course);
        // This is a single activity course format, always show the course menu on the activity main page.
        if ($context->contextlevel == CONTEXT_MODULE &&
                !$courseformat->has_view_page()) {

            $this->page->navigation->initialise();
            $activenode = $this->page->navigation->find_active_node();
            // If the settings menu has been forced then show the menu.
            if ($this->page->is_settings_menu_forced()) {
                $showcoursemenu = true;
            } else if (!empty($activenode) && ($activenode->type == navigation_node::TYPE_ACTIVITY ||
                    $activenode->type == navigation_node::TYPE_RESOURCE)) {

                // We only want to show the menu on the first page of the activity. This means
                // the breadcrumb has no additional nodes.
                if ($currentnode && ($currentnode->key == $activenode->key && $currentnode->type == $activenode->type)) {
                    $showcoursemenu = true;
                }
            }
        }

        // This is the site front page.
        if ($context->contextlevel == CONTEXT_COURSE &&
                !empty($currentnode) &&
                $currentnode->key === 'home') {
            $showfrontpagemenu = true;
        }

        // This is the user profile page.
        if ($context->contextlevel == CONTEXT_USER &&
                !empty($currentnode) &&
                ($currentnode->key === 'myprofile')) {
            $showusermenu = true;
        }


        if ($showfrontpagemenu) {
            $settingsnode = $this->page->settingsnav->find('frontpage', navigation_node::TYPE_SETTING);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showcoursemenu) {
            $settingsnode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showusermenu) {
            // Get the course admin node from the settings navigation.
            $settingsnode = $this->page->settingsnav->find('useraccount', navigation_node::TYPE_CONTAINER);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $this->build_action_menu_from_navigation($menu, $settingsnode);
            }
        }

        return $this->render($menu);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the most specific thing from the settings block. E.g. Module administration.
     *
     * @return string
     */
    public function region_main_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();

        if ($context->contextlevel == CONTEXT_MODULE) {

            $this->page->navigation->initialise();
            $node = $this->page->navigation->find_active_node();
            $buildmenu = false;
            // If the settings menu has been forced then show the menu.
            if ($this->page->is_settings_menu_forced()) {
                $buildmenu = true;
            } else if (!empty($node) && ($node->type == navigation_node::TYPE_ACTIVITY ||
                    $node->type == navigation_node::TYPE_RESOURCE)) {

                $items = $this->page->navbar->get_items();
                $navbarnode = end($items);
                // We only want to show the menu on the first page of the activity. This means
                // the breadcrumb has no additional nodes.
                if ($navbarnode && ($navbarnode->key === $node->key && $navbarnode->type == $node->type)) {
                    $buildmenu = true;
                }
            }
            if ($buildmenu) {
                // Get the course admin node from the settings navigation.
                $node = $this->page->settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }

        } else if ($context->contextlevel == CONTEXT_COURSECAT) {
            // For course category context, show category settings menu, if we're on the course category page.
            if ($this->page->pagetype === 'course-index-category') {
                $node = $this->page->settingsnav->find('categorysettings', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }

        } else {
            $items = $this->page->navbar->get_items();
            $navbarnode = end($items);

            if ($navbarnode && ($navbarnode->key === 'participants')) {
                $node = $this->page->settingsnav->find('users', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }

            }
        }
        return $this->render($menu);
    }

    /**
     * Take a node in the nav tree and make an action menu out of it.
     * The links are injected in the action menu.
     *
     * @param action_menu $menu
     * @param navigation_node $node
     * @param boolean $indent
     * @param boolean $onlytopleafnodes
     * @return boolean nodesskipped - True if nodes were skipped in building the menu
     */
    protected function build_action_menu_from_navigation(action_menu $menu,
                                                       navigation_node $node,
                                                       $indent = false,
                                                       $onlytopleafnodes = false) {
        $skipped = false;
        // Build an action menu based on the visible nodes from this navigation tree.
        foreach ($node->children as $menuitem) {
            if ($menuitem->display) {
                if ($onlytopleafnodes && $menuitem->children->count()) {
                    $skipped = true;
                    continue;
                }
                if ($menuitem->action) {
                    if ($menuitem->action instanceof action_link) {
                        $link = $menuitem->action;
                        // Give preference to setting icon over action icon.
                        if (!empty($menuitem->icon)) {
                            $link->icon = $menuitem->icon;
                        }
                    } else {
                        $link = new action_link($menuitem->action, $menuitem->text, null, null, $menuitem->icon);
                    }
                } else {
                    if ($onlytopleafnodes) {
                        $skipped = true;
                        continue;
                    }
                    $link = new action_link(new moodle_url('#'), $menuitem->text, null, ['disabled' => true], $menuitem->icon);
                }
                if ($indent) {
                    $link->add_class('m-l-1');
                }
                if (!empty($menuitem->classes)) {
                    $link->add_class(implode(" ", $menuitem->classes));
                }

                $menu->add_secondary_action($link);
                $skipped = $skipped || $this->build_action_menu_from_navigation($menu, $menuitem, true);
            }
        }
        return $skipped;
    }

    /**
     * Secure login info.
     *
     * @return string
     */
    public function secure_login_info() {
        return $this->login_info(false);
    }

    /**
     * Displays Leftmenu links added from respective plugins using the function in lib.php as "plugintype_pluginname_leftmenunode()
     * The links are injected in the left menu.
     *
     * @return HTML
     */
    public function left_navigation_quick_links(){
        global $DB, $CFG, $USER, $PAGE;
        $systemcontext = context_system::instance();
        $core_component = new core_component();
        $block_content = '';
        $local_pluginlist = $core_component::get_plugin_list('local');
        $block_pluginlist = $core_component::get_plugin_list('block');

        $block_content .= html_writer::start_tag('ul', array('class'=>'pull-left user_navigation_ul'));
            //======= Dasboard link ========//  
            $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_dashboard', 'class'=>'pull-left user_nav_div dashboard'));
                $button1 = html_writer::link($CFG->wwwroot, '<i class="fa fa-home" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('leftmenu_dashboard', 'theme_odl').'</span>', array('class'=>'user_navigation_link'));
                $block_content .= $button1;
            $block_content .= html_writer::end_tag('li');

            //=======Leader Dasboard link ========// 
            $gamificationb_plugin_exist = $core_component::get_plugin_directory('block', 'gamification');
            $gamificationl_plugin_exist = $core_component::get_plugin_directory('local', 'gamification');
            if($gamificationl_plugin_exist && $gamificationb_plugin_exist){
                $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_gamification_leaderboard', 'class'=>'pull-left user_nav_div notifications'));
                $gamification_url = new moodle_url('/blocks/gamification/dashboard.php');
                $gamification = html_writer::link($gamification_url, '<i class="fa fa-trophy"></i><span class="user_navigation_link_text">'.get_string('leftmenu_gmleaderboard','theme_odl').'</span>',array('class'=>'user_navigation_link'));
                $block_content .= $gamification;
                $block_content .= html_writer::end_tag('li');
            }

            $pluginnavs = array();
            foreach($local_pluginlist as $key => $local_pluginname){
                if(file_exists($CFG->dirroot.'/local/'.$key.'/lib.php')){
                    require_once($CFG->dirroot.'/local/'.$key.'/lib.php');
                    $functionname = 'local_'.$key.'_leftmenunode';
                    if(function_exists($functionname)){
                        $data = $functionname();
                        foreach($data as  $key => $val){
                            $pluginnavs[$key][] = $val;
                        }
                    }
                }
            }
            ksort($pluginnavs);
            foreach($pluginnavs as $pluginnav){
                foreach($pluginnav  as $key => $value){
                        $data = $value;
                        $block_content .= $data;
                }
            }

            $users_plugin_exist = $core_component::get_plugin_directory('local', 'users');
            if(!empty($users_plugin_exist)){
                $is_supervisor = $DB->record_exists('user', array('open_supervisorid' => $USER->id));
                if(!empty($is_supervisor)){
                    $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_teamdashboard', 'class'=>'pull-left user_nav_div teamdashboard'));
                        $supervisor_url = html_writer::link($CFG->wwwroot.'/local/users/team.php',
                                            '<i class="fa fa-users"></i>
                                            <span class="user_navigation_link_text">'.get_string('leftmenu_tm_dashboard', 'theme_odl').'</span>',
                                            array('class'=>'user_navigation_link'));
                        $block_content .= $supervisor_url;
                    $block_content .= html_writer::end_tag('li');
                }
            }
            foreach($block_pluginlist as $key => $local_pluginname){
                 if(file_exists($CFG->dirroot.'/blocks/'.$key.'/lib.php')){
                    require_once($CFG->dirroot.'/blocks/'.$key.'/lib.php');
                    $functionname = 'block_'.$key.'_leftmenunode';
                    if(function_exists($functionname)){
                    $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_dashboard', 'class'=>'pull-left user_nav_div dashboard'));
                        $data = $functionname();
                        //print_object($data);
                         $block_content .= $data;
                    $block_content .= html_writer::end_tag('li');
                    }
                }
            }
                        
            /*Site Administration Link*/
            if(is_siteadmin()){
                $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_adminstration', 'class'=>'pull-left user_nav_div adminstration'));
                    $admin_url = new moodle_url('/admin/search.php');
                    $admin = html_writer::link($admin_url, '<i class="fa fa-cogs"></i><span class="user_navigation_link_text">'.get_string('leftmenu_adminstration','theme_odl').'</span>',array('class'=>'user_navigation_link'));
                    $block_content .= $admin;
                $block_content .= html_writer::end_tag('li');
            }
        $block_content .= html_writer::end_tag('ul');
        
        return $block_content;
    }


    /**
     * Displays Leftmenu links added from respective plugins using the function in lib.php as "plugintype_pluginname_leftmenunode()
     * The links are injected in the left menu.
     *
     * @return HTML
     */
    public function user_nav_header_menu(){
        global $DB, $CFG, $USER, $PAGE;
        $systemcontext = context_system::instance();
        $core_component = new core_component();
        $block_content = '';
        $local_pluginlist = $core_component::get_plugin_list('local');
        $block_pluginlist = $core_component::get_plugin_list('block');

        $block_content .= html_writer::start_tag('div', array('class'=>'pull-right user_nav_container dropdown'));
            $block_content .= html_writer::link('javascript:void(0)', '<i class="icon fa fa-th grid_icon_topbar" aria-hidden="true"></i>', array('class'=>'user_navigation_link dropdown-toggle', 'data-toggle' => 'dropdown'));

            $block_content .= html_writer::start_tag('ul', array('class'=>'user_nav_header_menu dropdown-menu'));
                //======= Dasboard link ========//  
                $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_dashboard', 'class'=>'pull-left user_nav_div dashboard dropdown-item'));
                    $button1 = html_writer::link($CFG->wwwroot, '<i class="fa fa-home" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('leftmenu_dashboard', 'theme_odl').'</span>', array('class'=>'user_navigation_link'));
                    $block_content .= $button1;
                $block_content .= html_writer::end_tag('li');

                //=======Leader Dasboard link ========// 
                $gamificationb_plugin_exist = $core_component::get_plugin_directory('block', 'gamification');
                $gamificationl_plugin_exist = $core_component::get_plugin_directory('local', 'gamification');
                if($gamificationl_plugin_exist && $gamificationb_plugin_exist){
                    $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_gamification_leaderboard', 'class'=>'pull-left user_nav_div notifications dropdown-item'));
                    $gamification_url = new moodle_url('/blocks/gamification/dashboard.php');
                    $gamification = html_writer::link($gamification_url, '<i class="fa fa-trophy"></i><span class="user_navigation_link_text">'.get_string('leftmenu_gmleaderboard','theme_odl').'</span>',array('class'=>'user_navigation_link'));
                    $block_content .= $gamification;
                    $block_content .= html_writer::end_tag('li');
                }

                $pluginnavs = array();
                foreach($local_pluginlist as $key => $local_pluginname){
                    if(file_exists($CFG->dirroot.'/local/'.$key.'/lib.php')){
                        require_once($CFG->dirroot.'/local/'.$key.'/lib.php');
                        $functionname = 'local_'.$key.'_leftmenunode';
                        if(function_exists($functionname)){
                            $data = $functionname();
                            foreach($data as  $key => $val){
                                $pluginnavs[$key][] = $val;
                            }
                        }
                    }
                }
                ksort($pluginnavs);
                foreach($pluginnavs as $pluginnav){
                    foreach($pluginnav  as $key => $value){
                            $data = $value;
                            $block_content .= $data;
                    }
                }

                $users_plugin_exist = $core_component::get_plugin_directory('local', 'users');
                if(!empty($users_plugin_exist)){
                    $is_supervisor = $DB->record_exists('user', array('open_supervisorid' => $USER->id));
                    if(!empty($is_supervisor)){
                        $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_teamdashboard', 'class'=>'pull-left user_nav_div teamdashboard dropdown-item'));
                            $supervisor_url = html_writer::link($CFG->wwwroot.'/local/users/team.php',
                                                '<i class="fa fa-users"></i>
                                                <span class="user_navigation_link_text">'.get_string('leftmenu_tm_dashboard', 'theme_odl').'</span>',
                                                array('class'=>'user_navigation_link'));
                            $block_content .= $supervisor_url;
                        $block_content .= html_writer::end_tag('li');
                    }
                }
                foreach($block_pluginlist as $key => $local_pluginname){
                     if(file_exists($CFG->dirroot.'/blocks/'.$key.'/lib.php')){
                        require_once($CFG->dirroot.'/blocks/'.$key.'/lib.php');
                        $functionname = 'block_'.$key.'_leftmenunode';
                        if(function_exists($functionname)){
                        $block_content .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_dashboard', 'class'=>'pull-left user_nav_div dashboard dropdown-item'));
                            $data = $functionname();
                            //print_object($data);
                             $block_content .= $data;
                        $block_content .= html_writer::end_tag('li');
                        }
                    }
                }
                
            $block_content .= html_writer::end_tag('ul');
        $block_content .= html_writer::end_tag('div');
        
        return $block_content;
    }



    /**
     * return custom course page header buttons to show only on course pages
     *
     * @return HTML
     */
    public function course_context_header_settings_menu(){
        global $PAGE, $COURSE, $DB, $USER;
        $programcourse = optional_param('programcourse', 0, PARAM_RAW);
        $courseid = $COURSE->id;
        $sesskey = sesskey();
        if($courseid < 2){
            return '';
        }
        $PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'init');
        $PAGE->requires->js_call_amd('local_mooccourses/courseAjaxform', 'init');
        $return = '';

        $systemcontext = context_system::instance();
         if(has_capability('local/costcenter_course:view', $systemcontext) || has_capability('local/costcenter_course:manage', $systemcontext) || is_siteadmin()) {
            $admin_default_menu = true;
        }
        $useredit = '';
        if ($PAGE->user_is_editing() && $PAGE->user_allowed_editing()) {
            $useredit = 'off';
        }else{
            $useredit = 'on';
        }
        if($this->page->pagetype!='local-catalog-courseinfo') {
            if ($PAGE->user_allowed_editing()){
                    $categorycontext = context_coursecat::instance($COURSE->category);
                    $allow_editing = true;
                $editing_url = new moodle_url('/course/view.php', array('id' => $courseid, 'sesskey'=> $sesskey, 'edit'=>$useredit));
            }
            if(has_capability('moodle/course:create',$systemcontext) || is_siteadmin() ||
                                has_capability('local/costcenter_course:enrol', $systemcontext)) {
                // $is_courseedit_icon = true;
                // $course_reports =  true;
                // $course_complition = true;
                /*<revathi> ODL-831 starts*/
                if($COURSE->forpurchaseindividually == null || $COURSE->forpurchaseindividually == 2){
                    $is_courseedit_icon = true;
                    // $course_reports =  true;
                    // $course_complition = true;
                 }
                // else{
                    $course_reports =  true;
                    $course_complition = true;
                // }
                /*<revathi> ODL-831 ends*/
            }
            if(has_capability('moodle/backup:backupcourse',$systemcontext) || is_siteadmin()) {
                $coursebackup = true;
            }
            if(is_siteadmin() || has_capability('enrol/manual:manage', $systemcontext)) {
                $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $courseid ,'enrol' => 'manual'));
// <mallikarjun> - ODL-645 enrol icon hide -- starts
                //if($COURSE->forpurchaseindividually != null){
                if( $COURSE->affiliationstatus == 1){
                $userenrollment = true;
                }
                $facultyroleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
// <mallikarjun> - ODL-645 enrol icon hide -- ends
            }
        }

        $program = $DB->get_record('local_program', array('id' => $programcourse));

        $curriculum = $DB->get_record('local_curriculum', array('program' => $programcourse));
        
        $course_context = [
            "courseid" => $courseid,
            "admin_default_menu" => $admin_default_menu,
            //revathi Issue 831 starts//
            "forpurchaseindividually" => $COURSE->forpurchaseindividually ? : 0,
            //revathi Issue 831 ends//
            "default_menu" => $this->context_header_settings_menu(),
            "allow_editing" => $allow_editing,
            "editing_url" => $editing_url,
            "useredit" => $useredit,
            "is_courseedit_icon" => $is_courseedit_icon,
            "course_reports" => $course_reports,
            "course_complition" => $course_complition,
            "coursebackup" => $coursebackup,
            "enrolid" => $enrolid,
            "userenrollment" => $userenrollment,
            "categorycontextid" =>$categorycontext->id,
            'cfg' => $CFG,
            'programcourse' => $programcourse,
            'linkpath' => "$CFG->wwwroot/local/program/view.php?ccid=$curriculum->id&prgid=$program->id",
            'costcenterid' => $COURSE->open_costcenterid,
            'roleid' => $facultyroleid
        ];
        return $this->render_from_template('theme_odl/course_context_header', $course_context);
    }

    /**
     * returns the login logo url if uploaded in theme settings else returns false
     *
     * @return URL
     */
    function loginlogo(){

        $loginlogo = $this->page->theme->setting_file_url('loginlogo', 'loginlogo');
        if(empty($loginlogo)){
            $loginlogo = $this->image_url('login_logo', 'theme_odl');
        }
        return $loginlogo;
    }

    /**
     * returns the login desc text given in theme settings
     *
     * @return HTML
     */
    function logintext(){

        $logintext = $this->page->theme->settings->logindesc;
        if(empty($logintext)){
            $logintext = '';
        }
        return $logintext;
    }
    
    /**
     * Path for the selected font will return default as 0: lato
     *
     * @param array('Lato', 'Open Sans', 'PT Sans', 'Roboto', 'Maven Pro', 'Comfortaa')
     * @return url path for the selected font family name
     */
    function get_font_path(){
        $return = '';
        $font_value = get_config('theme_odl', 'font');
        $return = new moodle_url('/theme/odl/fonts/poppins.css');
            
        return $return;
    }

    /**
     * Returns the url of the uploaded favicon in theme settings.
     *
     * @return URL
     */
    public function favicon() {
        $favicon = $this->page->theme->setting_file_url('faviconurl', 'faviconurl');
        if (empty($favicon)) {
            $favicon = $this->image_url('favicon', 'theme');
        }

        return $favicon;
    }

    /* 
     * returns the images slider for the login page.
     * @author Raghuvaran Komati.
     * 
     * @return URL
    */
    public function loginslider(){
        global $CFG;
        if(isloggedin()){
            return false;
        }
        $loginslider = '';
        $loginslider .='<script> function loginpopup(test) {
                            $("#div_loginpopup_"+test).toggleClass("open");
                                        
                            }
                            function closeonclick(test){
                                $("#div_loginpopup_"+test).toggleClass("open");
                            }
                        </script>';

        $slider_context = [
            "img1_url" => $this->page->theme->setting_file_url('slider1', 'slider1'),
            "img2_url" => $this->page->theme->setting_file_url('slider2', 'slider2'),
            "img3_url" => $this->page->theme->setting_file_url('slider3', 'slider3'),
            "img4_url" => $this->page->theme->setting_file_url('slider4', 'slider4'),
            "img5_url" => $this->page->theme->setting_file_url('slider5', 'slider5'),
        ];
        $loginslider .= $this->render_from_template('theme_odl/slider', $slider_context);
        return $loginslider;
    }

    /**
     * Returns the Help button text of the given helpdesc in theme settings.
     *
     * @return HTML
     */
    public function helpbtn() {
        $helptext = $this->page->theme->settings->helpdesc;
        if(!empty($helptext)){
            $helpbtn = $helptext;
        }else{
            $helpbtn = '';
        }
        return $helpbtn;
    }

    /**
     * Returns the About button text of the given aboutus in theme settings.
     *
     * @return HTML
     */
    public function aboutbtn() {
        $aboutustext = $this->page->theme->settings->aboutus;
        if(!empty($aboutustext)){
            $aboutusbtn = $aboutustext;
        }else{
            $aboutusbtn = '';
        }
        return $aboutusbtn;
    }

    /**
     * Returns the Contact button text of the given contact in theme settings.
     *
     * @return HTML
     */
    public function contactbtn() {
        $contactustext = $this->page->theme->settings->contact;
        if(!empty($contactustext)){
            $contactusbtn = $contactustext;
        }else{
            $contactusbtn = '';
        }
        return $contactusbtn;
    }

    /*
     * Returns logo url to be displayed throughout the site
     * @author Rizwana
     *
     * @return logo url
    */
    public function get_custom_logo() {
       global $CFG, $USER, $DB;
       if(!empty($USER->open_costcenterid)){
            $costcenterid = $DB->get_field('local_costcenter', 'shortname', array('id'=>$USER->open_costcenterid));
        }
        $logopath = $this->page->theme->setting_file_url('logo', 'logo');
        if(!empty($costcenterid)){
            $logopath = $CFG->wwwroot.'/local/costcenter/costcenterlogos/'.$costcenterid.'.png';
        }
        if(empty($logopath)) {
            $default_logo = $this->image_url('default_logo', 'theme_odl');
            $logopath = $default_logo;
        }
        return $logopath;
    }

    /*
     * Returns Org buttons view on the teamdashboard template
     * @author Rizwana
     *
     * @return HTML
    */
    public function employee_orgview_buttons() {
        $buttons = [
            "gridview" => new moodle_url('/local/users/team.php'),
            "treeview" => new moodle_url('/local/hierarchy/myteam.php'),
        ];
        return $this->render_from_template('theme_odl/orgviewbtns', $buttons);
    }

    /**
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null) {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();
        // If not logged in, show the typical not-logged-in string.
        if (!isloggedin()) {
            $returnstr = get_string('loggedinnot', 'moodle');
            if (!$loginpage) {
                $returnstr .= " (<a href=\"$loginurl\">" . get_string('login') . '</a>)';
            }
            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );

        }

        // If logged in as a guest user, show a string to that effect.
        if (isguestuser()) {
            $returnstr = get_string('loggedinasguest');
            if (!$loginpage && $withlinks) {
                $returnstr .= " (<a href=\"$loginurl\">".get_string('login').'</a>)';
            }

            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );
        }

        // Get some navigation opts.
        $opts = $this->theme_odl_user_get_user_navigation_info($user, $this->page, array('avatarsize' => 60));

        /*Start of the role Switch */
        $systemcontext = context_system::instance();
        $roles = get_user_roles($systemcontext, $USER->id);

        if (is_array($roles) && (count($roles) > 0)) {
            
            $switchrole = new stdClass(); /*Role for the Learner i.e user role */
            $switchrole->itemtype = 'link';
            /*$learner_record_sql = "SELECT id, name, shortname FROM {role} WHERE shortname = 'student' AND archetype = 'student'";
            $learnerroleid = $DB->get_record_sql($learner_record_sql);

            if(empty($learnerroleid->name)){
                $rolename = $learnerroleid->shortname;
            }else{
                $rolename = $learnerroleid->name;
            }*/
            $user_ra_array = $USER->access['ra']['/1'];
            
            if(is_array($user_ra_array)){
                // $highest_roleid = max(array_keys($user_ra_array));
                $highest_roleid = $USER->open_role;
            }else{
                $highest_roleid = 0;
            }
            
            $current_roleid = isset($USER->access['rsw']['/1']) ? $USER->access['rsw']['/1'] : $highest_roleid;
            
            if(!empty($learnerroleid)){
                if($learnerroleid->id == $current_roleid){
                    $disabled_role = 'user_role active_role';
                 }else{
                    $disabled_role = 'user_role';
                 }

                 $switchrole->url = new moodle_url('/my/switchrole.php', array('sesskey' => sesskey(),'confirm' => 1,'switchrole' => $learnerroleid->id));
                 $switchrole->pix = "i/user";
                 $switchrole->title = get_string('switchroleas','theme_odl').$rolename;
                 $switchrole->titleidentifier = 'switchrole_'.$learnerroleid->name.',moodle';
                 $switchrole->class = $disabled_role;
                 $opts->navitems[] = $switchrole;
             }
             if(count($roles) > 1){
                foreach($roles as $role){   /*Get all the roles assigned to the user for display */
                    if(empty($role->name)){
                        $rolename = $role->shortname;
                    }else{
                        $rolename = $role->name;
                    }

                    $switchrole = new stdClass();
                    $switchrole->itemtype = 'link';
        
                    if($role->roleid == $current_roleid){
                        $switchrole->url = new moodle_url('javascript:void(0)');
                        $disabled_role = 'user_role active_role';
                    }else{
                        $switchrole->url = new moodle_url('/my/switchrole.php', array('sesskey' => sesskey(),'confirm' => 1,'switchrole' => $role->roleid));
                        $disabled_role = 'user_role';
                    }
                    $switchrole->pix = "i/switchrole";
                    $switchrole->title = get_string('switchroleas','theme_odl').$rolename;
                    $switchrole->titleidentifier = 'switchrole_'.$rolename.',moodle';
                    $switchrole->class = $disabled_role;
                    $opts->navitems[] = $switchrole;
                }
            }
        }
        if((isset($USER->access['rsw']) && empty($USER->access['rsw'])) ){
            if($highest_roleid){
                $this->role_switch_basedon_userroles($highest_roleid, false);
                $returnurl = new \moodle_url('/my/index.php');
                redirect($returnurl);
            }
        }elseif((isset($USER->access['rsw']) && $USER->access['rsw']) ){
            $highest_roleid = current($USER->access['rsw']);
        }
        // Build a logout link.
        $logout = new stdClass();
        $logout->itemtype = 'link';
        $logout->url = new moodle_url('/login/logout.php', array('sesskey' => sesskey()));
        $logout->pix = "a/logout";
        $logout->title = get_string('logout');
        $logout->titleidentifier = 'customlogout,moodle';
        $opts->navitems[] = $logout;


        $avatarclasses = "avatars";
        $avatarcontents = html_writer::span($opts->metadata['useravatar'], 'avatar current');
        $usertextcontents = $opts->metadata['userfullname'];

        // Other user.
        if (!empty($opts->metadata['asotheruser'])) {
            $avatarcontents .= html_writer::span(
                $opts->metadata['realuseravatar'],
                'avatar realuser'
            );
            $usertextcontents = $opts->metadata['realuserfullname'];
            $usertextcontents .= html_writer::tag(
                'span',
                get_string(
                    'loggedinas',
                    'moodle',
                    html_writer::span(
                        $opts->metadata['userfullname'],
                        'value'
                    )
                ),
                array('class' => 'meta viewingas')
            );
        }

        // Role.
        if (!empty($opts->metadata['asotherrole'])) {
            $role = core_text::strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['rolename'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['rolename'],
                'meta role role-' . $role
            );
        }

        // User login failures.
        if (!empty($opts->metadata['userloginfail'])) {
            $usertextcontents .= html_writer::span(
                $opts->metadata['userloginfail'],
                'meta loginfailures'
            );
        }

        // MNet.
        if (!empty($opts->metadata['asmnetuser'])) {
            $mnet = strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['mnetidprovidername'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['mnetidprovidername'],
                'meta mnet mnet-' . $mnet
            );
        }

        $returnstr .= html_writer::span(
            // html_writer::span($usertextcontents, 'usertext') .
            html_writer::span($avatarcontents, $avatarclasses),
            'userbutton'
        );

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->set_menu_trigger(
            $returnstr
        );
        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        if ($withlinks) {
            $navitemcount = count($opts->navitems);
            $idx = 0;
            foreach ($opts->navitems as $key => $value) {

                switch ($value->itemtype) {
                    case 'divider':
                        // If the nav item is a divider, add one and skip link processing.
                        $am->add($divider);
                        break;

                    case 'invalid':
                        // Silently skip invalid entries (should we post a notification?).
                        break;

                    case 'link':
                        // Process this as a link item.
                        
                        $pix = null;
                        if (isset($value->pix) && !empty($value->pix)) {
                            $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                        } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                                $value->title = html_writer::img(
                                $value->imgsrc,
                                $value->title,
                                array('class' => 'iconsmall')
                            ) . $value->title;
                        }
                        $stringtitleidentifier = $value->titleidentifier;
                        $component = explode(',', $stringtitleidentifier);
                        $component = $component[0];
                        if(($component == 'switchroleto') || ($component == 'logout')){
                            //do nothing
                        }elseif((strpos('switchrole_', $component) !== false)){
                            $al = new action_menu_link_secondary(
                                $value->url,
                                $pix,
                                $value->title,
                                array('class' => 'icon')
                            );
                            if (!empty($value->titleidentifier)) {
                                $al->attributes['data-title'] = $value->titleidentifier;
                            }
                            $al->attributes['class'] = $disabled_role;
                            $am->add($al);
                        }elseif((strpos('customlogout', $component) !== false)){
                            $al = new action_menu_link_secondary(
                                $value->url,
                                $pix,
                                $value->title,
                                array('class' => 'icon')
                            );
                            if (!empty($value->titleidentifier)) {
                                $al->attributes['data-title'] = $value->titleidentifier;
                            }
                            $am->add($al);
                        }else{
                            if(isset($value->class)){
                                $valueclass = $value->class;
                            }else{
                                $valueclass = '';
                            }
                            $al = new action_menu_link_secondary(
                                $value->url,
                                $pix,
                                $value->title,
                                //$value->class,
                                array('class' => 'icon '.$valueclass.'')
                            );
                            if (!empty($value->titleidentifier)) {
                                $al->attributes['data-title'] = $value->titleidentifier;
                            }
                            $am->add($al);
                        }

                        break;
                }

                $idx++;

                // Add dividers after the first item and before the last item.
                if ($idx == 1 || $idx == $navitemcount - 1) {
                    $am->add($divider);
                }
            }
        }

        return html_writer::div(
            parent::render($am),
            $usermenuclasses
        );
    }

    /**
 * Get a list of essential user navigation items.
 *
 * @param stdclass $user user object.
 * @param moodle_page $page page object.
 * @param array $options associative array.
 *     options are:
 *     - avatarsize=35 (size of avatar image)
 * @return stdClass $returnobj navigation information object, where:
 *
 *      $returnobj->navitems    array    array of links where each link is a
 *                                       stdClass with fields url, title, and
 *                                       pix
 *      $returnobj->metadata    array    array of useful user metadata to be
 *                                       used when constructing navigation;
 *                                       fields include:
 *
 *          ROLE FIELDS
 *          asotherrole    bool    whether viewing as another role
 *          rolename       string  name of the role
 *
 *          USER FIELDS
 *          These fields are for the currently-logged in user, or for
 *          the user that the real user is currently logged in as.
 *
 *          userid         int        the id of the user in question
 *          userfullname   string     the user's full name
 *          userprofileurl moodle_url the url of the user's profile
 *          useravatar     string     a HTML fragment - the rendered
 *                                    user_picture for this user
 *          userloginfail  string     an error string denoting the number
 *                                    of login failures since last login
 *
 *          "REAL USER" FIELDS
 *          These fields are for when asotheruser is true, and
 *          correspond to the underlying "real user".
 *
 *          asotheruser        bool    whether viewing as another user
 *          realuserid         int        the id of the user in question
 *          realuserfullname   string     the user's full name
 *          realuserprofileurl moodle_url the url of the user's profile
 *          realuseravatar     string     a HTML fragment - the rendered
 *                                        user_picture for this user
 *
 *          MNET PROVIDER FIELDS
 *          asmnetuser            bool   whether viewing as a user from an
 *                                       MNet provider
 *          mnetidprovidername    string name of the MNet provider
 *          mnetidproviderwwwroot string URL of the MNet provider
 */
function theme_odl_user_get_user_navigation_info($user, $page, $options = array()) {
    global $OUTPUT, $DB, $SESSION, $CFG;

    $returnobject = new stdClass();
    $returnobject->navitems = array();
    $returnobject->metadata = array();

    $course = $page->course;

    // Query the environment.
    $context = context_course::instance($course->id);

    if($user->id == 2){
        $url = new moodle_url('/user/profile.php', array(
            'id' => $user->id
        ));
    }else{
        $url = new moodle_url('/local/users/profile.php', array(
            'id' => $user->id
        ));
    }
    
    // Get basic user metadata.
    $returnobject->metadata['userid'] = $user->id;
    $returnobject->metadata['userfullname'] = fullname($user, true);
    $returnobject->metadata['userprofileurl'] = $url;

    $avataroptions = array('link' => false, 'visibletoscreenreaders' => false);
    if (!empty($options['avatarsize'])) {
        $avataroptions['size'] = $options['avatarsize'];
    }
    $returnobject->metadata['useravatar'] = $OUTPUT->user_picture (
        $user, $avataroptions
    );
    // Build a list of items for a regular user.

    // Query MNet status.
    if ($returnobject->metadata['asmnetuser'] = is_mnet_remote_user($user)) {
        $mnetidprovider = $DB->get_record('mnet_host', array('id' => $user->mnethostid));
        $returnobject->metadata['mnetidprovidername'] = $mnetidprovider->name;
        $returnobject->metadata['mnetidproviderwwwroot'] = $mnetidprovider->wwwroot;
    }

    // Did the user just log in?
    if (isset($SESSION->justloggedin)) {
        // Don't unset this flag as login_info still needs it.
        if (!empty($CFG->displayloginfailures)) {
            // Don't reset the count either, as login_info() still needs it too.
            if ($count = user_count_login_failures($user, false)) {

                // Get login failures string.
                $a = new stdClass();
                $a->attempts = html_writer::tag('span', $count, array('class' => 'value'));
                $returnobject->metadata['userloginfail'] =
                    get_string('failedloginattempts', '', $a);

            }
        }
    }

    // Links: Dashboard.
    $myhome = new stdClass();
    $myhome->itemtype = 'link';
    $myhome->url = new moodle_url('/my/');
    $myhome->title = get_string('mymoodle', 'admin');
    $myhome->titleidentifier = 'mymoodle,admin';
    $myhome->pix = "i/dashboard";
    $returnobject->navitems[] = $myhome;

    // Links: My Profile.
    if($user->id == 2){
        $url = new moodle_url('/user/profile.php', array(
            'id' => $user->id
        ));
    }else{
        $url = new moodle_url('/local/users/profile.php', array(
            'id' => $user->id
        ));
    }
    $myprofile = new stdClass();
    $myprofile->itemtype = 'link';
    $myprofile->url = $url;
    $myprofile->title = get_string('profile');
    $myprofile->titleidentifier = 'profile,moodle';
    $myprofile->pix = "i/user";
    $returnobject->navitems[] = $myprofile;

    $returnobject->metadata['asotherrole'] = false;

    // Before we add the last items (usually a logout + switch role link), add any
    // custom-defined items.
    $customitems = user_convert_text_to_menu_items($CFG->customusermenuitems, $page);
    foreach ($customitems as $item) {
        $returnobject->navitems[] = $item;
    }


    if ($returnobject->metadata['asotheruser'] = \core\session\manager::is_loggedinas()) {
        $realuser = \core\session\manager::get_realuser();

        // Save values for the real user, as $user will be full of data for the
        // user the user is disguised as.
        $returnobject->metadata['realuserid'] = $realuser->id;
        $returnobject->metadata['realuserfullname'] = fullname($realuser, true);
        if($realuser->id == 2){
            $url = new moodle_url('/user/profile.php', array(
                'id' => $realuser->id
            ));
        }else{
            $url = new moodle_url('/local/users/profile.php', array(
                'id' => $realuser->id
            ));
        }
        $returnobject->metadata['realuserprofileurl'] = $url;
        $returnobject->metadata['realuseravatar'] = $OUTPUT->user_picture($realuser, $avataroptions);

        // Build a user-revert link.
        $userrevert = new stdClass();
        $userrevert->itemtype = 'link';
        $userrevert->url = new moodle_url('/course/loginas.php', array(
            'id' => $course->id,
            'sesskey' => sesskey()
        ));
        $userrevert->pix = "a/logout";
        $userrevert->title = get_string('logout');
        $userrevert->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $userrevert;

    } else {

        // Build a logout link.
        $logout = new stdClass();
        $logout->itemtype = 'link';
        $logout->url = new moodle_url('/login/logout.php', array('sesskey' => sesskey()));
        $logout->pix = "a/logout";
        $logout->title = get_string('logout');
        $logout->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $logout;
    }

    if (is_role_switched($course->id)) {
        if ($role = $DB->get_record('role', array('id' => $user->access['rsw'][$context->path]))) {
            // Build role-return link instead of logout link.
            $rolereturn = new stdClass();
            $rolereturn->itemtype = 'link';
            $rolereturn->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'sesskey' => sesskey(),
                'switchrole' => 0,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $rolereturn->pix = "a/logout";
            $rolereturn->title = get_string('switchrolereturn');
            $rolereturn->titleidentifier = 'switchrolereturn,moodle';
            $returnobject->navitems[] = $rolereturn;

            $returnobject->metadata['asotherrole'] = true;
            $returnobject->metadata['rolename'] = role_get_name($role, $context);

        }
    } else {
        // Build switch role link.
        $roles = get_switchable_roles($context);
        if (is_array($roles) && (count($roles) > 0)) {
            $switchrole = new stdClass();
            $switchrole->itemtype = 'link';
            $switchrole->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'switchrole' => -1,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $switchrole->pix = "i/switchrole";
            $switchrole->title = get_string('switchroleto');
            $switchrole->titleidentifier = 'switchroleto,moodle';
            $returnobject->navitems[] = $switchrole;
        }
    }

    return $returnobject;
}

    /**
     * Number of switchable roles as buttons.
     *
     * @param int $courseid A course object.
     * @param stdClass $context usually site context.
     * @return string HTML.
     */
    public function get_switchable_roles_content($courseid, $context){
        global $USER, $DB;

        if(is_siteadmin($USER->id)){
            return false;
        }
        $return = '';
        $systemcontext = context_system::instance();
        
        $roles = get_switchable_roles($systemcontext);
        $roleid = isset($USER->access['rsw']['/1']) ? $USER->access['rsw']['/1'] : -1;
        $rolerecord = $DB->get_record('role', array('id'=>$roleid));
        foreach ($roles as $key => $value) {

            //$url = new moodle_url('/course/switchrole.php', array('id' => $courseid, 'switchrole' => $key, 'sesskey' => sesskey()));
            $url = new moodle_url('/my/switchrole.php', array('id' => $courseid, 'switchrole' => $key, 'sesskey' => sesskey(), 'confirm' => 1));
            // Button encodes special characters, apply htmlspecialchars_decode() to avoid double escaping.
            //echo $OUTPUT->container($OUTPUT->single_button($url, htmlspecialchars_decode($role)), 'm-x-3 m-b-1');
            $return .= html_writer::link($url, $value, array('class' => 'btn mr-10 role_button'));
        }
        return $return;
    }

    /**
     * Number of role switch based on user roles
     *
     * @param int $courseid A course object.
     * @param stdClass $context usually site context.
     * @return string HTML.
     */
    function role_switch_basedon_userroles($roleid, $purge){
        global $DB, $CFG, $USER;
        if(is_siteadmin($USER->id) || ($roleid <= 0) || $purge){
            return false;
        }
        $role = $DB->get_record('role', array('id' => $roleid));
        if(!$role){
            print_error('nopermission');
        }
        $systemcontext = context_system::instance();
        $roles = get_user_roles($systemcontext, $USER->id);
        $userroles = array();

        foreach($roles as $r){
            $userroles[$r->roleid] = $r->shortname;
        }
        $accessdata = get_empty_accessdata();
        if($this->roleswitch($roleid, $systemcontext, $accessdata)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * sitelevel roleswitch as buttons.
     *
     * @param int $courseid A course object.
     * @param stdClass $context usually site context.
     * @return string HTML.
     */
    function roleswitch($roleid, $context, &$accessdata){

        global $DB, $ACCESSLIB_PRIVATE, $USER;
        $USER->access['rsw'][$context->path] = $roleid;
       /* Get the relevant rolecaps into rdef
        * - relevant role caps
        *   - at ctx and above
        *   - below this ctx
        */

        if (empty($context->path)) {
            // weird, this should not happen
            return;
        }

        list($parentsaself, $params) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'pc_');
        $params['roleid'] = $roleid;
        $params['childpath'] = $context->path.'/%';

        $sql = "SELECT ctx.path, rc.capability, rc.permission
                  FROM {role_capabilities} rc
                  JOIN {context} ctx ON (rc.contextid = ctx.id)
                 WHERE rc.roleid = :roleid AND (ctx.id $parentsaself OR ctx.path LIKE :childpath)
              ORDER BY rc.capability"; // fixed capability order is necessary for rdef dedupe
        $rs = $DB->get_recordset_sql($sql, $params);

        $newrdefs = array();
        foreach ($rs as $rd) {
            $k = $rd->path.':'.$roleid;
            if (isset($accessdata['rdef'][$k])) {
                continue;
            }
            $newrdefs[$k][$rd->capability] = (int)$rd->permission;
        }
        $rs->close();

        // share new role definitions
        foreach ($newrdefs as $k=>$unused) {
            if (!isset($ACCESSLIB_PRIVATE->rolepermissions[$k])) {
                $ACCESSLIB_PRIVATE->rolepermissions[$k] = $newrdefs[$k];
            }
            $accessdata['rdef'][$k] =& $ACCESSLIB_PRIVATE->rolepermissions[$k];
        }
        return true;
    }
    
    /**
     * Returns standard navigation between activities in a course.
     *
     * @return string the navigation HTML.
     */
    public function activity_navigation() {
        // First we should check if we want to add navigation.
        $context = $this->page->context;
        if (($this->page->pagelayout !== 'incourse' && $this->page->pagelayout !== 'frametop')
            || $context->contextlevel != CONTEXT_MODULE) {
            return '';
        }

        // If the activity is in stealth mode, show no links.
        if ($this->page->cm->is_stealth()) {
            return '';
        }

        // Get a list of all the activities in the course.
        $course = $this->page->cm->get_course();
        $modules = get_fast_modinfo($course->id)->get_cms();

        // Put the modules into an array in order by the position they are shown in the course.
        $mods = [];
        $activitylist = [];
        foreach ($modules as $module) {
            // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
            if (!$module->uservisible || $module->is_stealth() || empty($module->url)) {
                continue;
            }
            $mods[$module->id] = $module;

            // No need to add the current module to the list for the activity dropdown menu.
            if ($module->id == $this->page->cm->id) {
                continue;
            }
            // Module name.
            $modname = $module->get_formatted_name();
            // Display the hidden text if necessary.
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }
            // Module URL.
            $linkurl = new moodle_url($module->url, array('forceview' => 1));
            // Add module URL (as key) and name (as value) to the activity list array.
            $activitylist[$linkurl->out(false)] = $modname;
        }

        $nummods = count($mods);

        // If there is only one mod then do nothing.
        if ($nummods == 1) {
            return '';
        }

        // Get an array of just the course module ids used to get the cmid value based on their position in the course.
        $modids = array_keys($mods);

        // Get the position in the array of the course module we are viewing.
        $position = array_search($this->page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        // Check if we have a previous mod to show.
        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        // Check if we have a next mod to show.
        if ($position < ($nummods - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new \core_course\output\activity_navigation($prevmod, $nextmod, $activitylist);
        $renderer = $this->page->get_renderer('core', 'course');
        if ($course->id != 1 OR is_siteadmin())
        return $renderer->render($activitynav);
    }

    /**
     * Accessibility: Left arrow-like character is
     * used in the breadcrumb trail, course navigation menu
     * (previous/next activity), calendar, and search forum block.
     * If the theme does not set characters, appropriate defaults
     * are set automatically. Please DO NOT
     * use &lt; &gt; &raquo; - these are confusing for blind users.
     *
     * @return string
     */
    public function larrow() {
        global $PAGE;
        $context_level = $PAGE->context;
        if($context_level->contextlevel == 70){
            return '<i class="fa fa-angle-left arrow_left"></i>';
        }else{
            return parent::larrow();
        }
    }

    /**
     * Accessibility: Right arrow-like character is
     * used in the breadcrumb trail, course navigation menu
     * (previous/next activity), calendar, and search forum block.
     * If the theme does not set characters, appropriate defaults
     * are set automatically. Please DO NOT
     * use &lt; &gt; &raquo; - these are confusing for blind users.
     *
     * @return string
     */
    public function rarrow() {
        global $PAGE;
        $context_level = $PAGE->context;
        if($context_level->contextlevel == 70){
            return '<i class="fa fa-angle-right arrow_right"></i>';
        }else{
            return parent::rarrow();
        }
    }

    /**
     * returns the link of the costcenter scheme css file to load in header of every layout
     * MAY BE CHANGED IN THE COMING VERSIONS
     *
     * @return URL
     */
    function get_costcenter_scheme_css(){
        global $CFG;
        require_once($CFG->dirroot.'/theme/odl/lib.php');
        if(file_exists($CFG->dirroot . '/local/costcenter/lib.php')){
            require_once($CFG->dirroot . '/local/costcenter/lib.php');
            $costcenter = new costcenter();
            $costcenter_scheme = $costcenter->get_costcenter_theme();
            $costcenter_scheme_url = theme_get_css_for_costcenter_scss($costcenter_scheme);
            if(!empty($costcenter_scheme_url)){
                $return = html_writer::empty_tag('link', array('href' => $costcenter_scheme_url, "rel"=> "stylesheet", "type" => "text/css"));
            }else{
                $return = false;
            }
        }
        return $return;
    }

    /**
     * returns the scheme names for theme and costcenter
     *
     * @return string 
     */
    function get_my_scheme(){
        global $PAGE, $CFG;

        $return = '';
        $theme_schemename = $PAGE->theme->settings->theme_scheme;
        if(!empty($theme_schemename)){
            $return .= ' theme_'.$theme_schemename;
        }
        if(file_exists($CFG->dirroot . '/local/costcenter/lib.php')){
            require_once($CFG->dirroot . '/local/costcenter/lib.php');
            $costcenter = new costcenter();
            $costcenter_schemename = $costcenter->get_costcenter_theme();
            if(!empty($costcenter_schemename)){
                $return .= ' organization_'.$costcenter_schemename;
            }
        }
        
        return $return;
    }

    /**
     *Function for copyright text
     *
     * @return string.
     */
    public function get_copyright_text() {
         return format_text($this->page->theme->settings->copyright, FORMAT_HTML);
    }

    /**
     *Function for footer social links
     * Returns a social links.
     *
     * @return social links.
     */
    public function footer_social_icons() {
        $hasfacebook    = (empty($this->page->theme->settings->facebook)) ? false : $this->page->theme->settings->facebook;
        $hastwitter     = (empty($this->page->theme->settings->twitter)) ? false : $this->page->theme->settings->twitter;
        $haslinkedin    = (empty($this->page->theme->settings->linkedin)) ? false : $this->page->theme->settings->linkedin;
        $hasyoutube     = (empty($this->page->theme->settings->youtube)) ? false : $this->page->theme->settings->youtube;
        $hasinstagram   = (empty($this->page->theme->settings->instagram)) ? false : $this->page->theme->settings->instagram;

        $socialcontext = [

            // If any of the above social networks are true, sets this to true.
            'hassocialnetworks' => ($hasfacebook || $hastwitter 
                 || $haslinkedin  || $hasyoutube ||  $hasinstagram
                 ) ? true : false,

            'socialicons' => array(
                    'facebook' => $hasfacebook,
                    'twitter'  => $hastwitter,
                    'linkedin' => $haslinkedin,
                    'youtube'    => $hasyoutube,
                    'instagram'  => $hasinstagram,
            )
        ];
        return $this->render_from_template('theme_odl/socialicons', $socialcontext);
    }

    /**
     * Overides the core user_picture function in output_renderers.php
     * overideen to show wavatar instead of user image
     *
     * @return string 
     */
    public function user_picture(stdClass $user, array $options = null) {
        global $CFG, $DB;
        $userpicture = new user_picture($user);
        $core_component = new core_component();
        foreach ((array)$options as $key=>$value) {
            if (array_key_exists($key, $userpicture)) {
                $userpicture->$key = $value;
            }
        }
        $wavatar_plugin_exist = $core_component::get_plugin_directory('local', 'wavatar');
        if(!empty($wavatar_plugin_exist)){
            if(!$user->picture && $myavatar = $DB->get_field('local_wavatar_info', 'path', array('userid' => $user->id))){
                $defaulturl = $CFG->wwwroot . '/local/wavatar/svgavatars/ready-avatars/'.$myavatar.''; // default image
                $defaultpic = '<img src='.$defaulturl.' alt="Picture of '.$user->firstname.' '.$user->lastname.'" title="Picture of '.$user->firstname.' '.$user->lastname.'" class="'.$userpicture->class.'" width = "'.$userpicture->size.'" height = "'.$userpicture->size.'" />';
                return $defaultpic;
            }
        }
        if ($user->picture == 0 || $user->picture > 0){
            return $this->render($userpicture);
        }
    }
}