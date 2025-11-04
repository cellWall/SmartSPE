<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

global $DB, $PAGE, $OUTPUT;

$cmid = required_param('cmid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/smartspe:manage', $context);

$PAGE->set_url('/mod/smartspe/question_selection.php', ['cmid' => $cmid, 'courseid' => $courseid]);
$PAGE->set_title('Select Questions');
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $selectedquestions = optional_param_array('questions', [], PARAM_INT);
    
    if (!empty($selectedquestions)) {
        // Save selected question IDs to the smartspe table
        $smartspe->questionids = implode(',', $selectedquestions);
        $DB->update_record('smartspe', $smartspe);
        
        redirect(
            new moodle_url('/mod/smartspe/view.php', ['id' => $cmid]),
            'Questions saved successfully',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// Get currently selected questions
$currentquestions = [];
if (!empty($smartspe->questionids)) {
    $currentquestions = explode(',', $smartspe->questionids);
}

// Get COURSE context instead of module context
$coursecontext = context_course::instance($courseid);

// Get all question categories in this COURSE (not just this module)
$categories = $DB->get_records('question_categories', ['contextid' => $coursecontext->id]);

// Get all questions from these categories
$questions = [];
if (!empty($categories)) {
    foreach ($categories as $category) {
        // Get questions using question_bank_entries (Moodle 4.x)
        $sql = "SELECT q.id, q.name, q.questiontext, qbe.questioncategoryid
                FROM {question} q
                JOIN {question_versions} qv ON qv.questionid = q.id
                JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                WHERE qbe.questioncategoryid = :categoryid
                AND qv.version = (
                    SELECT MAX(version) 
                    FROM {question_versions} 
                    WHERE questionbankentryid = qbe.id
                )
                ORDER BY q.name";
        
        $catquestions = $DB->get_records_sql($sql, ['categoryid' => $category->id]);
        
        if ($catquestions) {
            foreach ($catquestions as $q) {
                $q->categoryname = $category->name;
                $questions[$q->id] = $q;
            }
        }
    }
}

echo $OUTPUT->header();
?>

<h2>Select Questions for Smart SPE</h2>
<p>Choose the questions you want to include in this self and peer evaluation activity from all questions in this course. To create more questions, please go the the Question Bank</p>

<form method="post" action="">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    
    <?php if (empty($questions)): ?>
        <div class="alert alert-warning">
            No questions found in this course. 
            <a href="<?php echo (new moodle_url('/question/edit.php', ['cmid' => $cmid, 'courseid' => $courseid]))->out(false); ?>" class="btn btn-secondary">
                Create Questions in Question Bank
            </a>
        </div>
    <?php else: ?>
        <div class="question-selection-container">
            <?php 
            $lastcategory = null;
            foreach ($questions as $question): 
                if ($lastcategory !== $question->categoryname):
                    if ($lastcategory !== null) echo '</div>'; // Close previous category
                    ?>
                    <h4 class="mt-3"><?php echo s($question->categoryname); ?></h4>
                    <div class="category-questions ml-3">
                <?php 
                    $lastcategory = $question->categoryname;
                endif;
                
                $checked = in_array($question->id, $currentquestions) ? 'checked' : '';
                ?>
                
                <div class="form-check mb-2">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        name="questions[]" 
                        value="<?php echo $question->id; ?>" 
                        id="q<?php echo $question->id; ?>"
                        <?php echo $checked; ?>
                    >
                    <label class="form-check-label" for="q<?php echo $question->id; ?>">
                        <strong><?php echo s($question->name); ?></strong>
                        <br>
                        <small class="text-muted">
                            <?php echo strip_tags($question->questiontext); ?>
                        </small>
                    </label>
                </div>
                
            <?php endforeach; ?>
            </div> <!-- Close last category -->
        </div>
        
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Save Selected Questions</button>
            <a href="<?php echo (new moodle_url('/mod/smartspe/view.php', ['id' => $cmid]))->out(false); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    <?php endif; ?>
</form>

<?php
echo $OUTPUT->footer();