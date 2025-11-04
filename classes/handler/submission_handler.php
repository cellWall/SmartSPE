<?php

namespace mod_smartspe\handler;

use mod_smartspe\db_team_manager as team_manager;
use mod_smartspe\db_evaluation as evaluation;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

class submission_handler
{
    protected $evaluator;
    protected $courseid;
    protected $attemptid;

    
    public function __construct($evaluator, $courseid, $attemptid)
    {
        $this->evaluator = $evaluator;
        $this->courseid = $courseid;
        $this->attemptid = $attemptid;
    }

    /**
     * Wrapped in quiz_manager for handling submission and save data
     *
     * Called in quiz_manager.
     *
     * @param $answers answers array
     * @param $comment comment on members or self
     * @param $self_comment second self comment
     * @param $evaluateeid member being evaluated
     * 
     * @return boolean
     */
    public function is_submitted($answers, $evaluateeid, $comment, $self_comment = null)
    {
        $manager = new team_manager(); //Team management 
        $evaluation = new evaluation(); //evaluation database

        $userid = $this->evaluator;

        //Ensure both students exists
        if ($manager->record_exist('groups_members', ['userid' => $userid])
            && $manager->record_exist('groups_members', ['userid' => $evaluateeid]))
        {
            //Save evaluation info into database
            //Return true if data are saved in db successfully
            return $evaluation->save_answers_db($answers, $userid, 
                                            $evaluateeid, $this->courseid, $comment,
                                                $this->attemptid, $self_comment);
        }
        else
        {
            if(!$manager->record_exist('groups_members', ['userid' => $userid]))
                throw new moodle_exception("The student id ($userid) doesn't exist. <br>");
            else
                throw new moodle_exception("The evaluee id ($evaluateeid) doesn't exist. <br>");
        }
    }

    //If the submission is not done by time
    public function is_overdue()
    {
        $manager = new team_manager();
    }
}