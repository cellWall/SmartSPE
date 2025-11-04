<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_smartspe\event\attempt_start',
        'callback'    => 'mod_smartspe\observer::attempt_start',
        'internal'    => false,
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\mod_smartspe\event\attempt_finish',
        'callback'    => 'mod_smartspe\observer::attempt_finish',
        'internal'    => false,
        'priority'    => 9999,
    ],
];
