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
 * curriculum Capabilities
 *
 * curriculum - A Moodle plugin for managing ILT's
 *
 * @package     local_curriculum
 * @author:     Arun Kumar Mukka <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die;
$capabilities = array(
    'local/program:createprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:copyprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager' => CAP_ALLOW,
            'user' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deleteprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manageprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewcurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managecurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editcurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletecurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:createcurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:createsession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewsession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editsession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletesession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managesession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_ALLOW,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:assigntrainer' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managetrainer' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:addusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:removeusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manageusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_ALLOW,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:takesessionattendance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_ALLOW,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:takemultisessionattendance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:trainer_viewprogram' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_PREVENT,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:trainer_viewcurriculum' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_PREVENT,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_allprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_newprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_activeprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_holdprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_cancelledprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:view_completedprogramtab' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:createfeedback' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewfeedback' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editfeedback' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
          'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletefeedback' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managefeedback' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:addcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:createcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletecourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:removecourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managecourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manage_offlineclassroom' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:publish' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:cancel' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:release_hold' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:hold' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:complete' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manage_owndepartments' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_PREVENT,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manage_ownorganization' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_PREVENT,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:manage_multiorganizations' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'manager'          => CAP_PREVENT,
            'user'        => CAP_PREVENT,
            'student'      => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
    ),
    'local/program:programcompletion' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:createsemester' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewsemester' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editsemester' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletesemester' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managesemester' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:enrolcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'student'      => CAP_ALLOW,
        ),
    ),
    'local/program:createsemesteryear' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:viewsemesteryear' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:editsemesteryear' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:deletesemesteryear' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:managesemesteryear' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:affiliateprograms' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'coursecreator' => CAP_PREVENT,
           'teacher'        => CAP_PREVENT,
           'editingteacher' => CAP_PREVENT,
           'manager'          => CAP_ALLOW,
           'user'        => CAP_PREVENT,
           'student'      => CAP_PREVENT,
           'guest' => CAP_PREVENT
        ),
    ),
    'local/program:canaddfaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:cansetcost' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:canmanagefaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:canremovefaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:canviewfaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:importcoursecontent' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'manager'          => CAP_ALLOW
        ),
    ),
    'local/program:classroomcompletion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
    ),
);
