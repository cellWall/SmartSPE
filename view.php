<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER, $PAGE;

// get basic parameters
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST); 
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', array('id' => $cm->instance), '*', MUST_EXIST);
$instanceid = $smartspe->id;
require_login($course, true, $cm);
$cmid = $cm->id;

// set up the page
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'mod_smartspe'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// determine user role
$is_teacher = has_capability('mod/smartspe:manage', $context);
$is_student = !$is_teacher && has_capability('mod/smartspe:submit', $context);

if ($is_student) 
{
    // redirect students to the evaluation flow
    redirect(new moodle_url('/mod/smartspe/student_evaluate.php', ['id' => $cmid]));
    exit;
} 
else if ($is_teacher)
{
    // show teacher view
    echo $OUTPUT->header();
    
    $quiz_manager = new smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid, $cmid);
    $output = $PAGE->get_renderer('mod_smartspe');
    echo $output->render(new \mod_smartspe\output\teacher_view($quiz_manager));
    
    echo $OUTPUT->footer();
}

else 
{
    echo $OUTPUT->header();
    echo $OUTPUT->notification('You do not have permission to view this activity.', 'notifyproblem');
    echo $OUTPUT->footer();
}
