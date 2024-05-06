<?php
$observers = array(
    array(
        'eventname' => 'core\event\user_enrolment_created',
        'callback' => 'local_edwiserbridge_observer::user_enrolment_created',
    ),
    array(
       'eventname' => 'core\event\user_enrolment_deleted',
       'callback'  => 'local_edwiserbridge_observer::user_enrolment_deleted',
    ),
    array(
       'eventname' => 'core\event\user_created',
       'callback'  => 'local_edwiserbridge_observer::user_created',
    ),
    array(
       'eventname' => 'core\event\user_deleted',
       'callback'  => 'local_edwiserbridge_observer::user_deleted',
    )
);
