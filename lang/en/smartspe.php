<?php
// Standard Moodle language file for SmartSpe module.

defined('MOODLE_INTERNAL') || die();

// general names for SPE plugin & module
$string['pluginname'] = 'Smart Self Peer Evaluation';
$string['modulename'] = 'Self Peer Evaluation';
$string['modulenameplural'] = 'Self Peer Evaluations';

// names for form creation from UC/lecturer's view
$string['smartspe_name'] = 'Evaluation Form Name';           // Form field for activity name
$string['smartspe_name_help'] = 'Enter a descriptive name for this evaluation activity.';
$string['smartspe_intro'] = 'Description';
$string['selectquestion'] = 'Questions selection';
$string['choose'] = 'Please select questions';
$string['submissionperiod'] = 'Submission Period';      // Header in mod_form.php
$string['submissionstart'] = 'Submission Start Date';   // Start date label
$string['submissionend'] = 'Submission End Date';       // End date label

// names for form display for students
$string['submissionwindow'] = 'Submission Window';
$string['submissionopen'] = 'Submission is open';
$string['submissionclosed'] = 'Submission is closed';

//Events
$string['event_attempt_start'] = 'Attempt started';
$string['event_attempt_finish'] = 'Attempt finished';
$string['event_download'] = 'File downloaded';
$string['event_evaluation_after_duedate'] = 'Evaluation submitted after due date';

// Message provider strings.
$string['messageprovider:evaluation_notification'] = 'Evaluation submission notifications';
$string['messageprovider:evaluation_notification_desc'] = 'Notifications sent to users when evaluation is submitted or pending.';
$string['receivenotifications'] = 'Receive SmartSpe notifications';

$string['sentiment_analysis'] = 'Sentiment Analysis';
$string['statistical_analysis'] = 'Statistical Analysis';

// names for admin view
//Buttons
$string['startattempt'] = 'Attempt Evaluation';

$string['pluginadministration'] = 'Self Peer Evaluation administration'; // Admin menu
$string['privacy:metadata'] = 'The Self Peer Evaluation plugin stores user submissions and peer evaluation data.';

$string['submissionsuccess'] = 'All evaluations submitted successfully.';
?>
