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
 * A two column layout for the odl theme.
 *
 * @package   theme_odl
 * @copyright 2018 eAbyas Info Solutons Pvt Ltd, India
 * @author    eAbyas  <info@eAbyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');
if (isloggedin()) {
    $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
} else {
    $navdraweropen = false;
}
$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}

$schemename = $OUTPUT->get_my_scheme();
if(!empty($schemename)){
    $extraclasses[] = $schemename;
}

$is_loggedin = isloggedin();
$is_loggedin = empty($is_loggedin) ? false : true;

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockshtml = '';
$hasblocks = false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();

$layerone_detail_full = $OUTPUT->blocks('layerone_full', 'col-md-12');
$layerone_detail_one = $OUTPUT->blocks('layerone_one', 'col-md-7');
$layerone_detail_two = $OUTPUT->blocks('layerone_two', 'col-md-5');

$layertwo_detail_one = $OUTPUT->blocks('layertwo_one', 'col-md-12');
$layertwo_detail_two = $OUTPUT->blocks('layertwo_two', 'col-md-12');
$layertwo_detail_three = $OUTPUT->blocks('layertwo_three', 'col-md-6');
$layertwo_detail_four = $OUTPUT->blocks('layertwo_four', 'col-md-6');

$fontpath = $OUTPUT->get_font_path();

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'layerone_detail_full' => $layerone_detail_full,
    'layerone_detail_one' => $layerone_detail_one,
    'layerone_detail_two' => $layerone_detail_two,
    'layertwo_detail_one' => $layertwo_detail_one,
    'layertwo_detail_two' => $layertwo_detail_two,
    'layertwo_detail_three' => $layertwo_detail_three,
    'layertwo_detail_four' => $layertwo_detail_four,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'is_admin' => is_siteadmin(),
    'isloggedin' => $is_loggedin,
    'font_path' => $fontpath
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;
echo $OUTPUT->render_from_template('theme_odl/dashboard', $templatecontext);

