<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER;

// --- 1. Get basic parameters ---
$id = required_param('id', PARAM_INT); // Course module ID

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instanceid = $cm->instance;
require_login($course, true, $cm);

// --- Get teacher-selected questions from the module instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $instanceid], '*', MUST_EXIST);
$questionids = explode(',', $smartspe->questionids);

//Create attempt
//$attemptid = $quiz_manager->start_attempt_evaluation($data, $teacher_selected_questionids); // changed this function to align with the one from quiz_manager.php -- commenting this out because i don't think we have to create it here
$quiz_manager = new smartspe_quiz_manager($USER->id, $cm->course, $context, $instanceid);

echo '<pre>Questionids: ';
print_r($questionids);
echo '</pre>';

// --- Step 1: Get members of team ---
try {
    //Get member ids
    $members = $quiz_manager->get_members();
} catch (moodle_exception $e) {
    die("Error getting members: " . $e->getMessage());
}

// --- Step 2: For each member, start attempt and submit ---
foreach ($members as $memberid) 
{
    // --- Step 2a: Start attempt with teacher-selected question IDs ---
    try {
        $attemptid = $quiz_manager->start_attempt_evaluation($memberid, $questionids);
        echo "Attempt created for member $memberid: Attempt ID $attemptid<br>";
    } catch (moodle_exception $e) {
        echo "Failed to start attempt for member $memberid: " . $e->getMessage() . "<br>";
        continue;
    }

    // --- Step 2b: Prepare fake answers --
    $answers = [];
    $mcq_count = 0;
    $comment_count = 0;

    $questions = $quiz_manager->get_questions($questionids);
    $member = $DB->get_record('user', ['id' => $memberid]);
    $member_name = $member->firstname;

    //Check
    if (!$questions || !$questions[0]['qtype'])
    {
        echo "Question is empty (view.php) <br>";
        break;
    }

    $comment = null;

    foreach ($questions as $question) 
    {
        // echo 'QUESTION STRUCTURE: ';
        // echo '<h4>' . format_string($question['name']) . '</h4>';
        // echo format_text($question['text'], FORMAT_HTML);

        // // Access qtype safely
        // echo '<p><strong>Type:</strong> ' . $question['qtype'] . '</p>';

        // // If it has answers (for MCQ type)
        // if (!empty($question['answers'])) {
        //     echo '<ul>';
        //     foreach ($question['answers'] as $answer) {
        //         // $answer is an object (from question_bank)
        //         echo '<li>' . format_text($answer->answer, FORMAT_HTML) . '</li>';
        //     }
        //     echo '</ul>';
        // }

        // echo 'End of question structure<br>';

        $qtext = $question['text'];
        $qtype = $question['qtype'];
        echo "Question for $member_name: $qtext <br>";
        if ($question['qtype'] === 'multichoice' && $mcq_count < 5) 
        {
            $answers[$mcq_count] = rand(1, 5); // simulate MCQ answer
            $current_answer = $answers[$mcq_count];
            echo "Answer: $current_answer <br>";
            $mcq_count++;
        } 
        elseif ($question['qtype'] === 'essay' && $comment_count < 1) 
        {
            $comment = "Peer comment for member $memberid";
            echo "Comment: $comment <br>";
            $comment_count++;
        }
        else
        {
            echo "There is no match type ($qtype) <br>";
            break;
        }
    }
    
    if ($USER->id == $memberid)
    {
        $self_comment = "My self comment";
        echo "Self Comment: $self_comment <br>";
    }
    else
        $self_comment = null;

    echo '<pre>Review answers before autosave: ';
    print_r($answers);
    echo '</pre>';

    // --- Step 2c: Autosave ---
    try {
        $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, false);
        echo "Autosaved answers for member $memberid<br>";
    } catch (moodle_exception $e) {
        echo "Failed autosave for member $memberid: " . $e->getMessage() . "<br>";
    }

    // Reassign new random answers for MCQs
    foreach ($answers as $index => $ansvalue) {
        $answers[$index] = rand(1, 5);
    }

    echo '<pre>Review answers before submitting: ';
    print_r($answers);
    echo '</pre>';
    // --- Step 2d: Submit ---
    try {
        $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, true);
    } catch (moodle_exception $e) {
        echo "Submission error for member $memberid: " . $e->getMessage() . "<br>";
    }
}

//Final Submit
$submitted = $quiz_manager->quiz_is_submitted();
echo $submitted ? "Submitted evaluation<br>" : "Failed submission";

echo "<hr>Test completed.";

?>

<hr>
<h3>Download Test</h3>

<form method="get" action="">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    <input type="hidden" name="extension" value="csv">
    <button type="submit" name="download_csv" value="1" class="btn btn-primary">Download CSV</button>

</form>

<?php
// Check if download button clicked
if (optional_param('download_csv', 0, PARAM_INT)) {
    $extension = required_param('extension', PARAM_ALPHA);
    
    try {
        $quiz_manager->download_report($extension);
    } catch (moodle_exception $e) {
        echo '<div class="alert alert-danger">Download error: ' . $e->getMessage() . '</div>';
    }
}
?>
