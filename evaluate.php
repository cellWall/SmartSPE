<?php
// mod/smartspe/evaluate.php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // course module ID

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/smartspe:submit', $context); // Check student capability

// Set up page title and navigation
$pagetitle = get_string('evaluationform', 'mod_smartspe') . ': ' . format_string($smartspe->name);
$url = new moodle_url('/mod/smartspe/evaluate.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// --- MOCK EVALUATION DATA ---
// In a real app, this would fetch the questions linked to $smartspe->id
$mock_questions_for_form = [
    (object)['id' => 1, 'text' => 'Rate the **effort and quality** of your work on this project (1-5).'],
    (object)['id' => 2, 'text' => 'Rate your **teamwork and collaboration** skills within the group (1-5).'],
    (object)['id' => 3, 'text' => 'Please provide **open, qualitative** feedback for your peer (Student X).'],
];

// Start rendering
echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox mod-smartspe-evaluation');
echo '<h2>Self & Peer Evaluation Form</h2>';
echo '<p>You are evaluating: <strong>Student X (Peer)</strong>. You will submit a self-evaluation later.</p>';

echo '<form method="post" action="submit.php">';
echo '<input type="hidden" name="cmid" value="' . $cm->id . '" />';

$qcounter = 1;
foreach ($mock_questions_for_form as $q) {
    echo '<div class="form-group mb-4">';
    echo '<h3>' . $qcounter . '. ' . $q->text . '</h3>';

    if ($q->id <= 2) {
        // Mock rating scale (1-5)
        echo '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
        for ($i = 1; $i <= 5; $i++) {
            echo '<label class="btn btn-outline-primary">';
            echo '<input type="radio" name="response_' . $q->id . '" value="' . $i . '" autocomplete="off">' . $i;
            echo '</label>';
        }
        echo '</div>';
    } else {
        // Mock open text response
        echo '<textarea name="response_' . $q->id . '" class="form-control" rows="4"></textarea>';
    }
    echo '</div>';
    $qcounter++;
}

echo '<button type="submit" class="btn btn-success">Submit Evaluation</button>';
echo '</form>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();