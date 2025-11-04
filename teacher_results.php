<?php
require_once(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/smartspe:manage', $context);

$PAGE->set_url('/mod/smartspe/teacher_results.php', ['id' => $cmid]);
$PAGE->set_title('Evaluation Results');
$PAGE->set_heading($course->fullname);


echo $OUTPUT->header();

// Add download button
echo '<div class="mb-4 mt-3">';
echo '<a href="download_results.php?id=' . $cmid . '&type=csv" class="btn btn-primary me-2">
        <i class="fa fa-download"></i> Download result details (CSV)
      </a>';
echo '<a href="download_results.php?id=' . $cmid . '&type=xlsx" class="btn btn-success">
        <i class="fa fa-file-excel-o"></i> Download result summary (XLSX)
      </a>';
echo '</div>';

// Get all evaluations
$sql = "SELECT e.*, u1.firstname as eval_fname, u1.lastname as eval_lname,
               u2.firstname as evaluatee_fname, u2.lastname as evaluatee_lname
        FROM {smartspe_evaluation} e
        JOIN {user} u1 ON u1.id = e.evaluator
        JOIN {user} u2 ON u2.id = e.evaluatee
        WHERE e.course = :courseid
        ORDER BY u1.lastname, u2.lastname";

$evaluations = $DB->get_records_sql($sql, ['courseid' => $course->id]);

echo '<h3>All Evaluation Responses</h3>';
echo '<table class="generaltable table table-striped">';
echo '<thead><tr>
        <th>Evaluator</th>
        <th>Evaluatee</th>
        <th>Q1</th>
        <th>Q2</th>
        <th>Q3</th>
        <th>Q4</th>
        <th>Q5</th>
        <th>Average</th>
        <th>Comment</th>
      </tr></thead>';
echo '<tbody>';

foreach ($evaluations as $eval) {
    echo '<tr>';
    echo '<td>' . $eval->eval_fname . ' ' . $eval->eval_lname . '</td>';
    echo '<td>' . $eval->evaluatee_fname . ' ' . $eval->evaluatee_lname . '</td>';
    echo '<td>' . $eval->q1 . '</td>';
    echo '<td>' . $eval->q2 . '</td>';
    echo '<td>' . $eval->q3 . '</td>';
    echo '<td>' . $eval->q4 . '</td>';
    echo '<td>' . $eval->q5 . '</td>';
    echo '<td>' . number_format($eval->average, 2) . '</td>';
    echo '<td>' . s($eval->comment) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';

echo $OUTPUT->footer();