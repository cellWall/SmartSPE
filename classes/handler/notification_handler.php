<?php

namespace mod_smartspe\handler;

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

 class notification_handler
 {

    /**
     * Send notification to user email
     *
     * Called when student submitted the quiz.
     * Called in quiz_manager
     *
     * @param $userid the destination user
     * @return mixed
     */
    public function noti_eval_submitted($userid)
    {
        global $DB;

        // Get user info
        $user = $DB->get_record('user', ['id' => $userid]);

        //If user has no email
        if (!$user->email)
            throw new moodle_exception("User ({$user->id}, {$user->firstname}) has no email");

        $subject = "Self Peer evaluation Submission";

        $message = "Dear {$user->lastname} {$user->firstname},\n\n";
        $message .= "Student ({$user->id}, {$user->lastname} {$user->firstname}) has submitted self peer evaluation form!!\n\n\n\n";
        $message .= "Thank you for your time!!";

        // Moodle function to send email
        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_smartspe';
        $eventdata->name = 'evaluation_notification';
        $eventdata->userfrom = \core\user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = nl2br($message);
        $eventdata->smallmessage = $subject;

        return message_send($eventdata);
    }

    /**
     * Send notification to user email
     *
     * Called before submission deadline
     * Called in quiz_manager
     *
     * @param $userid the destination user
     * @return mixed
     */
    public function noti_before_submission($userid)
    {
        global $DB;

        // Get user info
        $user = $DB->get_record('user', ['id' => $userid]);

        //If user has no email
        if (!$user->email)
            throw new moodle_exception("User ({$user->id}, {$user->firstname}) has no email");

        $subject = "Reminder: Please complete Self Peer Evaluation before the due date";

        $message = "Dear {$user->lastname} {$user->firstname},\n\n";
        $message .= "Student ({$user->id}, {$user->lastname} {$user->firstname}) has not submitted self peer evaluation form yet!!\n\n";
        $message .= "Please submit the self peer evaluation form before the due date!!\n\n\n Thank you!!";

        // Moodle function to send email
        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_smartspe';
        $eventdata->name = 'evaluation_notification';
        $eventdata->userfrom = \core\user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = nl2br($message);
        $eventdata->smallmessage = $subject;

        return message_send($eventdata);
    }

    /**
     * Send notification to user email
     *
     * Called after submission deadline.
     * Called in quiz_manager
     *
     * @param $userid the destination user
     * @return mixed
     */
    public function after_due_date($userid)
    {
        global $DB;

        // Get user info
        $user = $DB->get_record('user', ['id' => $userid]);

        //If user has no email
        if (!$user->email)
            throw new moodle_exception("User ({$user->id}, {$user->firstname}) has no email");

        $subject = "Failed to submit Self Peer evaluation";

        $message = "Dear {$user->lastname} {$user->firstname},\n\n";
        $message .= "Student ({$user->id}, {$user->lastname} {$user->firstname}) has not submitted self peer evaluation form!! 
        before the due date and failed to submit after the due date\n";

        // Moodle function to send email
        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_smartspe';
        $eventdata->name = 'evaluation_notification';
        $eventdata->userfrom = \core\user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = nl2br($message);
        $eventdata->smallmessage = $subject;

        return message_send($eventdata);
    }
 }