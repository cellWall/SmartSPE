<?php
require_once(__DIR__ . '/../../config.php'); // Moodle bootstrap

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($cm->course, false, $cm);
require_capability('mod/smartspe:viewstatsdashboard', $context);

// --- Page setup ---
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/smartspe/stat_dashboard.php', ['id' => $cmid]));
$PAGE->set_title('SmartSPE Stats Dashboard');
$PAGE->set_heading('SmartSPE Stats Dashboard');

// --- Output ---
echo $OUTPUT->header();

// button for sentiment analysis report
echo html_writer::tag('h2', 'Sentiment Analysis Report');
echo '<a href="download_results.php?id=' . $cmid . '&type=csv" class="btn btn-primary me-2">
        <i class="fa fa-download"></i> Download Sentiment Analysis Report (CSV)
      </a>';

// Build iframe URL (note: no /moodle prefix)
global $CFG;
$chart_url = $CFG->wwwroot . '/mod/smartspe/pix/stats_dashboard.html';

// Debug output to confirm
// echo '<p>DEBUG iframe src: ' . $chart_url . '</p>';

echo html_writer::start_div('smartspe-dashboard', ['style' => 'padding:20px;']);
echo html_writer::tag('iframe', '', [
    'src' => $chart_url,
    'width' => '100%',
    'height' => '700',
    'style' => 'border:none;'
]);
echo html_writer::end_div();

echo $OUTPUT->footer();
