  <?php
  $capabilities = array(
    'local/mooccourses:manage' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
       // 'admin'=>CAP_ALLOW
        )
    ),
  'local/mooccourses:create' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'local/mooccourses:view' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW, 
        )
    ),
  'local/mooccourses:edit' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
             'manager' => CAP_ALLOW,
	      'admin'        => CAP_ALLOW  
        )
    ),
      'local/mooccourses:delete' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        'manager' => CAP_ALLOW,
	      'admin'        => CAP_ALLOW  
        )
    ),
    'local/mooccourses:affiliatemooccourses' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
  );
