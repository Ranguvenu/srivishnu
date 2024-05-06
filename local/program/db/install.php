<?php
defined('MOODLE_INTERNAL') || die();
function xmldb_local_program_install() {
    global $CFG, $DB;
 	$usertours = $CFG->dirroot . '/local/program/usertours/';
    $totalusertours = count(glob($usertours . '*.json'));
    $usertoursjson = glob($usertours . '*.json');
    $pluginmanager = new \tool_usertours\manager();
    for ($i = 0; $i < $totalusertours; $i++) {
        $importurl = $usertoursjson[$i];
        if (file_exists($usertoursjson[$i])
                && pathinfo($usertoursjson[$i], PATHINFO_EXTENSION) == 'json') {
            $data = file_get_contents($importurl);
            $tourconfig = json_decode($data);
            $tourexists = $DB->record_exists('tool_usertours_tours', array('name' => $tourconfig->name));
            if (!$tourexists) {
                $tour = $pluginmanager->import_tour_from_json($data);
            }
        }
    }
}