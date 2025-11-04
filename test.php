<?php
require_once('../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/mod/sentiment_analysis/test.php'));
$PAGE->set_title('Test Sentiment Analysis DB');
$PAGE->set_heading('Test Sentiment Analysis Database');

echo $OUTPUT->header();
global $DB;

// Handle form actions
$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);
$name   = optional_param('name', '', PARAM_TEXT);
$teamcode = optional_param('teamcode', '', PARAM_TEXT);
$course = optional_param('course', 0, PARAM_INT);

// Insert
if ($action === 'add') {
    $record = new stdClass();
    $record->name = $name;
    $record->teamcode = $teamcode;
    $record->course = $course;
    $DB->insert_record('team', $record);
}

// Delete
if ($action === 'delete' && $id) {
    $DB->delete_records('team', ['id' => $id]);
}

// Update
if ($action === 'update' && $id) {
    $record = $DB->get_record('team', ['id' => $id], '*', MUST_EXIST);
    $record->name = $name;
    $record->teamcode = $teamcode;
    $record->course = $course;
    $DB->update_record('team', $record);
}

// Fetch all teams
$teams = $DB->get_records('team');

echo '<h2>Teams</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>ID</th><th>Team Code</th><th>Name</th><th>Course</th><th>Actions</th></tr>';
foreach ($teams as $t) {
    echo '<tr>';
    echo '<td>'.$t->id.'</td>';
    echo '<td>'.$t->teamcode.'</td>';
    echo '<td>'.$t->name.'</td>';
    echo '<td>'.$t->course.'</td>';
    echo '<td>
        <a href="?action=delete&id='.$t->id.'">Delete</a> |
        <a href="?action=edit&id='.$t->id.'">Edit</a>
    </td>';
    echo '</tr>';
}
echo '</table>';

// Form for add or update
$edit = null;
if ($action === 'edit' && $id) {
    $edit = $DB->get_record('team', ['id' => $id], '*', MUST_EXIST);
}
?>
<h3><?php echo $edit ? 'Update Team' : 'Add Team'; ?></h3>
<form method="post">
    <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
    <?php if ($edit) { ?>
        <input type="hidden" name="id" value="<?php echo $edit->id; ?>">
    <?php } ?>
    Team Code: <input type="text" name="teamcode" value="<?php echo $edit ? $edit->teamcode : ''; ?>"><br>
    Name: <input type="text" name="name" value="<?php echo $edit ? $edit->name : ''; ?>"><br>
    Course ID: <input type="number" name="course" value="<?php echo $edit ? $edit->course : ''; ?>"><br>
    <input type="submit" value="<?php echo $edit ? 'Update' : 'Add'; ?>">
</form>

<?php
echo $OUTPUT->footer();
