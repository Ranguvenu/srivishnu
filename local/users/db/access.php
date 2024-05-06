<?php
/*
 * This program is free software; you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation; either version 3 of the License, or     
 * (at your option) any later version.                                   
 *                                                                       
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
*/

$capabilities = array(

    // Managing course custom fields
	 'local/users:manage' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
	     // 'admin'=>CAP_ALLOW
        )
    ),
  'local/users:create' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'local/users:view' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
	     // 'admin'        => CAP_ALLOW,
//            'teacher'        => CAP_ALLOW,
//	     'student'      => CAP_ALLOW 
        )
    ),

   'local/users:edit' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
             'manager' => CAP_ALLOW,
	     // 'admin'        => CAP_ALLOW  
        )
    ),
      'local/users:delete' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
             'manager' => CAP_ALLOW,
	     // 'admin'        => CAP_ALLOW  
        )
    )

    
);

