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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Jahnavi
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;

class plugin_activefacultycolumns extends pluginbase {

    public function init() {
        $this->fullname = get_string('activefacultycolumns', 'block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('activefaculty');
    }

    public function summary($data) {
        return format_string($data->columname);
    }

    public function colformat($data) {
        $align = (isset($data->align)) ? $data->align : '';
        $size = (isset($data->size)) ? $data->size : '';
        $wrap = (isset($data->wrap)) ? $data->wrap : '';
        return array($align, $size, $wrap);
    }

    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $DB;
        switch($data->column){
            case 'filesuploaded':
                if(!isset($row->filesuploaded) && isset($data->subquery)){
                    $filesuploaded =  $DB->get_field_sql($data->subquery);
                 }else{
                    $filesuploaded = $row->{$data->column};
                 }
                 $row->{$data->column} = !empty($filesuploaded) ? $filesuploaded : 0;
            break;
            case 'activitiescreated':
                if(!isset($row->activitiescreated) && isset($data->subquery)){
                    $activitiescreated =  $DB->get_field_sql($data->subquery);
                 }else{
                    $activitiescreated = $row->{$data->column};
                 }
                 $row->{$data->column} = !empty($activitiescreated) ? $activitiescreated : 0;
            break;
            default:
                return (isset($row->{$data->column}))? $row->{$data->column} : '--';
            break;

        }
        return (isset($row->{$data->column}))? $row->{$data->column} : ' -- ';
    }
}