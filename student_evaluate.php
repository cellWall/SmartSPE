<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\event\attempt_start;

global $DB, $USER, $PAGE, $OUTPUT;

// Get parameters
$cmid = required_param('id', PARAM_INT);
$evaluateeid = optional_param('evaluateeid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Load course module
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/smartspe:submit', $context);

// Set up page
$PAGE->set_url('/mod/smartspe/student_evaluate.php', ['id' => $cmid]);
$PAGE->set_title('Self & Peer Evaluation');
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Create quiz manager
$quiz_manager = new \mod_smartspe\smartspe_quiz_manager(
    $USER->id, 
    $course->id, 
    $context, 
    $smartspe->id, 
    $cmid
);

// Get question IDs
$questionids = !empty($smartspe->questionids) 
    ? array_map('intval', explode(',', trim($smartspe->questionids))) 
    : [];

if (empty($questionids)) 
{
    echo $OUTPUT->header();
    echo $OUTPUT->notification('No questions have been set up for this evaluation.', 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

// Get team members
$members = $quiz_manager->get_members();
if (empty($members)) 
{
    echo $OUTPUT->header();
    echo $OUTPUT->notification('You must be in a group to complete this evaluation.', 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

// Determine evaluation sequence: self first, then peers
$member_ids = array_column($members, 'id');

// If no evaluateeid, start with self
if ($evaluateeid == 0) 
{
    $evaluateeid = $USER->id;
}

$type = ($evaluateeid == $USER->id) ? 'self' : 'peer';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) 
{
    
    // Collect answers
    $answers = [];
    $comment = '';
    
    foreach ($questionids as $idx => $qid)
    {
        $answer_key = 'answer_' . ($idx + 1);
        $answer = optional_param($answer_key, '', PARAM_RAW);
        
        // If it's an essay question (contains more text), treat as comment
        if (strlen($answer) > 10 && strpos($answer, ' ') !== false) 
        {
            $comment = $answer;
        } 
        else 
        {
            $answers[] = intval($answer);
        }
    }
    
    // Start attempt for this evaluatee
    $attemptid = $quiz_manager->start_attempt_evaluation($evaluateeid, $questionids);
    
    // Determine if this is the last evaluation
    $current_index = array_search($evaluateeid, $member_ids);
    $is_last = ($current_index === count($member_ids) - 1);
    
    if ($action === 'next' || $is_last) 
    {
        // Save with finish = false (moving to next)
        $self_comment = ($type === 'self') ? $comment : null;
        $peer_comment = ($type === 'peer') ? $comment : null;
        
        $quiz_manager->process_attempt_evaluation(
            $answers, 
            $peer_comment, 
            $self_comment, 
            false  // Not finished yet
        );
        
        if ($is_last) 
        {
            $quiz_manager->process_attempt_evaluation(
            $answers, 
            $peer_comment, 
            $self_comment, 
            true  // finished
            );
            // This was the last person - now call final submission
            $quiz_manager->quiz_is_submitted();
            
            redirect(
                new moodle_url('/course/view.php', ['id' => $course->id]),
                get_string('submissionsuccess', 'mod_smartspe'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );

        } 
        else 
        {
            // Move to next person
            $next_index = $current_index + 1;
            $next_evaluateeid = $member_ids[$next_index];
            
            redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
                'id' => $cmid,
                'evaluateeid' => $next_evaluateeid
            ]));
        }

    } 
}

// Load saved answers if they exist
$saved_questions = [];
try 
{
    // Only try to load if an attempt exists for this evaluatee
    $members = $quiz_manager->get_members();
    $member_ids = array_column($members, 'id');
    
    if (in_array($evaluateeid, $member_ids)) 
    {
        // Check if attempt was already started before
        // If not, just use empty array
        $saved_questions = [];
    }
} 
catch (Exception $e) 
{
    $saved_questions = [];
}

// Render the page
echo $OUTPUT->header();

$output = $PAGE->get_renderer('mod_smartspe');
$studentview = new \mod_smartspe\output\student_view(
    $quiz_manager, 
    $evaluateeid, 
    $type, 
    $questionids,
    $saved_questions
);
echo $output->render($studentview);

echo $OUTPUT->footer();