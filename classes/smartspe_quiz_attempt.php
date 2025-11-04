<?php

namespace mod_smartspe;

use core\exception\moodle_exception;
use stdClass;
use mod_smartspe\handler\data_persistence;
use mod_smartspe\handler\questions_handler;

defined('MOODLE_INTERNAL') || die();

class smartspe_quiz_attempt
{
    protected $smartspeid; //Instance id
    protected $userid; //Evaluator id
    protected $attempt; //Attempt object
    protected $quba; //question_usage_by_activity
    protected $data_persistence; //Track student's answers
    protected $questions;
    protected $attemptid; //Attempt id
    protected $questionids;

    /**
     * Create attempt if not already created or else get retrieve the existing attempt
     *
     * Called when an attempt is created or continue processing from the existing attempt.
     *
     * @param $userid the evaluator id
     * @param $smartspeid the instance id
     * @param $memberid attempt on this member
     * @param $attemptid the current attemptid
     * @param $questionids the questionids getting from mod_smartspe_mod_form
     * @return void
     */
    public function __construct($smartspeid, $userid, $memberid, $questionids)
    {
        global $DB;

        if (empty($questionids))
            throw new moodle_exception("Questions are not properly selected");

        $this->smartspeid = $smartspeid;
        $this->userid = $userid;
        $this->questionids = $questionids;

        // Check if evaluator has already processed on this member
        $memberusage = $DB->get_record('smartspe_attempts', [
            'userid' => $userid,
            'memberid' => $memberid,
            'smartspeid' => $smartspeid
        ]);
        
        //If exist, get the attempt
        if ($memberusage)
        {
            $this->attempt = $DB->get_record('smartspe_attempts', 
                                ['userid' => $userid, 'memberid' => $memberid], '*', MUST_EXIST);
            //get attempt id
            $this->attemptid = $this->attempt->id;
        }
        else
        {
            $record = new stdClass();
            $record->smartspeid = $smartspeid;
            $record->userid = $userid;
            $record->memberid = $memberid;
            $record->timecreated = time();
            $record->timemodified = time();

            //Insert current attempt into database
            $this->attemptid = $DB->insert_record('smartspe_attempts', $record);
            //Get current attempt
            $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->attemptid]);
        }

    }

    public function get_attempt_questions()
    {
        return $this->questions;
    }

    /**
     * Create questions usage and link usage to each attempt
     * Make for data persistence purpose
     *
     * Called when a new instance of the module is created.
     * 
     *@param $context context of the questions
     *@param $memberid create persistence on this person
     * @return data_persistence $data_persistence 
     */
    public function create_persistence($context, $memberid)
    {
        global $DB;

        $question_handler = new questions_handler();

        //Check if the usage has already been created and linked
        $usage_exist = $DB->record_exists('question_usages', ['id' => $this->attempt->uniqueid]);

        // Load or create question usage
        if (!empty($this->attempt->uniqueid) && $usage_exist)
        {
            //Load questions 
            $this->data_persistence = new data_persistence($this->attemptid, $memberid);
            $this->questions = $this->data_persistence->load_attempt_questions();
        } 
        else 
        {
            //Cretae questions usage and link to each attempt
            $this->quba = $question_handler->add_all_questions($context, $this->questionids, $this->attemptid);
            $this->data_persistence = new data_persistence($this->attemptid, $memberid);
            $this->questions = $this->data_persistence->load_attempt_questions();
        }


        return $this->data_persistence;
    }

    public function get_attempt_id()
    {
        return $this->attemptid;
    }

}
