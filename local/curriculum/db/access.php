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
    'local/curriculum:createprogram' => array(
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
    'local/curriculum:copyprogram' => array(
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
    'local/curriculum:editprogram' => array(
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
    'local/curriculum:deleteprogram' => array(
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
    'local/curriculum:manageprogram' => array(
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
    'local/curriculum:viewprogram' => array(
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
    'local/curriculum:viewcurriculum' => array(
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
    'local/curriculum:managecurriculum' => array(
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
    'local/curriculum:editcurriculum' => array(
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
    'local/curriculum:deletecurriculum' => array(
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
    'local/curriculum:createcurriculum' => array(
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
    'local/curriculum:createsession' => array(
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
    'local/curriculum:viewsession' => array(
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
    'local/curriculum:editsession' => array(
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
    'local/curriculum:deletesession' => array(
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
    'local/curriculum:managesession' => array(
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
    'local/curriculum:assigntrainer' => array(
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
    'local/curriculum:managetrainer' => array(
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
    'local/curriculum:addusers' => array(
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
    'local/curriculum:removeusers' => array(
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
    'local/curriculum:manageusers' => array(
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
    'local/curriculum:viewusers' => array(
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
    'local/curriculum:takesessionattendance' => array(
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
    'local/curriculum:takemultisessionattendance' => array(
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
    'local/curriculum:trainer_viewprogram' => array(
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
    'local/curriculum:trainer_viewcurriculum' => array(
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
    'local/curriculum:view_allprogramtab' => array(
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
    'local/curriculum:view_newprogramtab' => array(
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
    'local/curriculum:view_activeprogramtab' => array(
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
    'local/curriculum:view_holdprogramtab' => array(
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
    'local/curriculum:view_cancelledprogramtab' => array(
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
    'local/curriculum:view_completedprogramtab' => array(
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
    'local/curriculum:createfeedback' => array(
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
    'local/curriculum:viewfeedback' => array(
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
    'local/curriculum:editfeedback' => array(
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
    'local/curriculum:deletefeedback' => array(
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
    'local/curriculum:managefeedback' => array(
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
    'local/curriculum:addcourse' => array(
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
    'local/curriculum:createcourse' => array(
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
    'local/curriculum:viewcourse' => array(
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
    'local/curriculum:editcourse' => array(
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
    'local/curriculum:deletecourse' => array(
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
    'local/curriculum:removecourse' => array(
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
    'local/curriculum:managecourse' => array(
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
    'local/curriculum:publish' => array(
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
    'local/curriculum:cancel' => array(
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
    'local/curriculum:release_hold' => array(
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
    'local/curriculum:hold' => array(
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
    'local/curriculum:complete' => array(
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
    'local/curriculum:manage_owndepartments' => array(
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
    'local/curriculum:manage_ownorganization' => array(
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
    'local/curriculum:manage_multiorganizations' => array(
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
    'local/curriculum:programcompletion' => array(
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
    'local/curriculum:createsemester' => array(
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
    'local/curriculum:viewsemester' => array(
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
    'local/curriculum:editsemester' => array(
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
    'local/curriculum:deletesemester' => array(
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
    'local/curriculum:managesemester' => array(
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
    'local/curriculum:enrolcourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'student'      => CAP_ALLOW,
        ),
    ),
    'local/curriculum:createsemesteryear' => array(
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
    'local/curriculum:viewsemesteryear' => array(
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
    'local/curriculum:editsemesteryear' => array(
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
    'local/curriculum:deletesemesteryear' => array(
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
    'local/curriculum:managesemesteryear' => array(
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
    'local/curriculum:affiliateprograms' => array(
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
    'local/curriculum:canaddfaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/curriculum:cansetcost' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/curriculum:canmanagefaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/curriculum:canremovefaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/curriculum:canviewfaculty' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager'          => CAP_ALLOW
        ),
    ),
    'local/curriculum:importcoursecontent' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
            'manager'          => CAP_ALLOW
        ),
    ),
);
