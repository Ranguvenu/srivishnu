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
 * @package    theme_odl
 * @copyright  Syed Hameed Ullah <hameed@eabyas.in>
 * @since 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_theme_odl_upgrade($oldversion) {
    global $DB, $CFG;
    if ($oldversion < 2017111300) {
        $block_instances = array();

        $quick_navigation_exist = core_component::get_plugin_directory('block', 'quick_navigation');
        if(!empty($quick_navigation_exist)){
            $block_instances[] = array(
                'blockname' => 'quick_navigation',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layerone_full',
                'defaultweight' => 0,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $userdashboard_exist = core_component::get_plugin_directory('block', 'userdashboard');
        if(!empty($userdashboard_exist)){
            $block_instances[] = array(
                'blockname' => 'userdashboard',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layerone_full',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $my_event_calendar_exist = core_component::get_plugin_directory('block', 'my_event_calendar');
        if(!empty($my_event_calendar_exist)){
            $block_instances[] = array(
                'blockname' => 'my_event_calendar',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layerone_one',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $achievements_exist = core_component::get_plugin_directory('block', 'achievements');
        if(!empty($achievements_exist)){
            $block_instances[] = array(
                'blockname' => 'achievements',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layerone_two',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $popular_courses_exist = core_component::get_plugin_directory('block', 'popular_courses');
        if(!empty($popular_courses_exist)){
            $block_instances[] = array(
                'blockname' => 'popular_courses',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layertwo_one',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $learnerscript_exist = core_component::get_plugin_directory('block', 'learnerscript');
        if(!empty($learnerscript_exist)){
            $block_instances[] = array(
                'blockname' => 'learnerscript',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layertwo_four',
                'defaultweight' => 0,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $announcement_exist = core_component::get_plugin_directory('block', 'announcement');
        if(!empty($announcement_exist)){
            $block_instances[] = array(
                'blockname' => 'announcement',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layertwo_two',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $poll_exist = core_component::get_plugin_directory('block', 'poll');
        if(!empty($poll_exist)){
            $block_instances[] = array(
                'blockname' => 'poll',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
                'defaultregion' => 'layertwo_three',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        }

        $block_instances[] = array(
                'blockname' => 'settings',
                'parentcontextid' => 1,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'course-view-*',
                'subpagepattern' => null,
                'defaultregion' => 'side-pre',
                'defaultweight' => 1,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time()
            );
        foreach ($block_instances as $block_instance) {
            $record_exists = $DB->record_exists('block_instances', array('blockname' => $block_instance['blockname'], 'pagetypepattern' => $block_instance['pagetypepattern']));
            if(!$record_exists){
                 $DB->insert_record('block_instances', $block_instance);
            }
        }
        upgrade_plugin_savepoint(true, 2017111300, 'theme', 'odl');
    }
    
    return true;
}