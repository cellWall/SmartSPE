<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER;

// 1. Get course module ID
$cmid = required_param('id', PARAM_INT);
$extension = optional_param('type', 'csv', PARAM_ALPHA);

// 2. Get Moodle context info
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// 3. Require login & capability
require_login($course, false, $cm);
require_capability('mod/smartspe:manage', $context);

// 4. Initialize quiz manager
$quizmanager = new smartspe_quiz_manager($USER->id, $course->id, $context, $smartspe->id, $cmid);

try 
{
    if($extension == "csv")
        $quizmanager->download_report_details($extension);
    if($extension == "xlsx")
        $quizmanager->download_report_summary($extension);
} catch (Exception $e) {
    throw new moodle_exception($e->getMessage());
}
