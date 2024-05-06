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
 * curriculum View
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_program\output;
defined('MOODLE_INTERNAL') || die;
use context_system;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class program implements renderable, templatable {
    /**
     * [__construct description]
     * @method __construct
     */
    public function __construct() {
        $this->context = context_system::instance();
        $this->plugintype = 'local';
        $this->plugin_name = 'curriculum';
    }
    /**
     * [export_for_template description]
     * @method export_for_template
     * @param  renderer_base       $output [description]
     * @return [type]                      [description]
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->contextid = $this->context->id;
        $data->plugintype = $this->plugintype;
        $data->plugin_name = $this->plugin_name;
        $data->creatacurriculum = ((has_capability('local/program:manageprogram',
            context_system::instance()) && has_capability('local/program:createprogram',
            context_system::instance())) || is_siteadmin()) ? true : false;
        return $data;
    }
}