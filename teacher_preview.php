<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

global $DB, $PAGE, $OUTPUT;

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/smartspe:manage', $context);

$PAGE->set_url('/mod/smartspe/teacher_preview.php', ['id' => $cmid]);
$PAGE->set_title('Preview Smart SPE Evaluation');
$PAGE->set_heading($course->fullname);

// --- Fetch selected questions ---
$questionids = !empty($smartspe->questionids)
    ? array_map('intval', explode(',', $smartspe->questionids))
    : [];

$questions = [];
if (!empty($questionids)) {
    list($insql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
    $sql = "SELECT id, name, questiontext, qtype
            FROM {question}
            WHERE id $insql
            ORDER BY FIELD(id, " . implode(',', $questionids) . ")";
    $questions = $DB->get_records_sql($sql, $params);
}

// --- Prepare questions and options for Mustache ---
$data = new stdClass();
$data->questions = [];

foreach ($questions as $q) {
    $questiondata = [
        'id' => $q->id,
        'name' => $q->name,
        'text' => strip_tags($q->questiontext),
        'options' => []
    ];

    // Fetch options only if the question is multiple choice or similar
    if ($q->qtype === 'multichoice' || $q->qtype === 'truefalse') {
        $options = $DB->get_records('question_answers', ['question' => $q->id], 'id ASC');
        foreach ($options as $opt) {
            $questiondata['options'][] = [
                'text' => strip_tags($opt->answer),
                'fraction' => $opt->fraction  // 100 = correct, 0 = wrong (can display if needed)
            ];
        }
    }

    $data->questions[] = $questiondata;
}

$data->returnurl = new moodle_url('/mod/smartspe/view.php', ['id' => $cmid]);

// --- Render ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_smartspe/teacher_preview', $data);
echo $OUTPUT->footer();
