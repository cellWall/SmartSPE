<?php
// mod/smartspe/reports.php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // course module ID

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/smartspe:viewreports', $context); // Check teacher capability

// Set up page title and navigation
$pagetitle = get_string('reports', 'mod_smartspe') . ': ' . format_string($smartspe->name);
$url = new moodle_url('/mod/smartspe/reports.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// --- MOCK REPORT DATA ---
// Since we can't access real data, we provide mock data to build the UI/Template.
$report_data = new stdClass();
$report_data->hasdata = true;
$report_data->sentiment_summary = 'Overall sentiment is highly positive (85%), suggesting good peer feedback tone.';
$report_data->stats_summary = 'The average score across all questions was 4.2 out of 5, with low standard deviation (0.3).';
$report_data->mock_table = [
    (object)['user' => 'Student A', 'score' => 4.5, 'sentiment' => 'Positive'],
    (object)['user' => 'Student B', 'score' => 3.9, 'sentiment' => 'Neutral'],
    (object)['user' => 'Student C', 'score' => 4.8, 'sentiment' => 'Very Positive'],
];

// Start rendering
echo $OUTPUT->header();

// We are rendering the mock data directly in the controller for simplicity.
// In a real app, you would use a dedicated renderer class here.
echo $OUTPUT->box_start('generalbox mod-smartspe-reports');
echo '<h2>' . get_string('sentiment_analysis', 'mod_smartspe') . '</h2>';
echo '<p>' . $report_data->sentiment_summary . '</p>';

echo '<h2>' . get_string('statistical_analysis', 'mod_smartspe') . '</h2>';
echo '<p>' . $report_data->stats_summary . '</p>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();