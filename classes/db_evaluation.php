<?php
namespace mod_smartspe;

use core\exception\moodle_exception;
use mod_smartspe\db_team_manager as team_manager;

class db_evaluation
{
    public function save_answers_db($answers, $userid, $evaluateeid, $courseid, $comment, $attemptid, $self_comment=null)
    {
        global $DB;

        $manager = new team_manager();
        $sum = 0;
        $nums = 0;

        //Call check function from team_manager
        //To confirm that userid is assigned to the team
        if ($manager->record_exist('groups_members', ['userid' => $userid]))
        {
            $record = new \stdClass();

            $record->evaluator = $userid;
            $record->evaluatee = $evaluateeid;
            $record->course = $courseid;
            $record->attemptid = $attemptid;

            //Loop all answers
            foreach ($answers as $index => $answer)
            {
                if (!$answer)
                    throw new moodle_exception("In db_evaluation: No answer added with answers[$index]");

                $sum += $answer;

                $field = 'q'.($index+1); //q1, q2, etc. (database column for questions)
                $record->$field = $answer;
                $nums++;
            }

            $record->average = $sum / $nums;
            
            // Flatten comment/self_comment if array
            if (is_array($comment)) {
                $comment = implode(', ', $comment);
            }
            if (is_array($self_comment)) {
                $self_comment = implode(', ', $self_comment);
            }

            $record->comment = $comment ?? '';
            if ($self_comment) {
                $record->self_comment = $self_comment;
            }

            $existing = $DB->get_record('smartspe_evaluation', ['attemptid' => $attemptid]);

            if (!$existing) {
                $evaluationid = $DB->insert_record('smartspe_evaluation', $record);
            } else {
                // Set the ID for update
                $record->id = $existing->id;
                if(!$DB->update_record('smartspe_evaluation', $record))
                    throw new moodle_exception("Error updating existing records");
                else
                    $evaluationid = $existing->id;
            }

        }
        else
        {
            throw new moodle_exception("This student {$userid} has not been assigned to any team");
        }

        return $evaluationid;
    }

    public function get_answers_db($userid)
    {
        global $DB;
        $answers = [];
        
        //get record
        $record = $DB->get_record('smartspe_evaluation', ['evaluator' => $userid]);
        
        if ($record)
        {
            for($i = 0; $i < 5; $i++)
            {   
                //Access questions column
                $field = 'q'.($i+1);
                $answers[$i] = $record->$field;
            }
        }

        return $answers; //Array

    }

    public function get_comment_db($userid)
    {
        global $DB;
        $comment = null;

        $record = $DB->get_record('smartspe_evaluation', ['evaluator' => $userid]);
        if ($record)
            $comment = $record->comment;

        return $comment;
    }

    public function get_self_comment_db($userid)
    {
        global $DB;
        $comment = null;

        $record = $DB->get_record('smartspe_evaluation', ['evaluator' => $userid]);
        if ($record)
            $comment = $record->self_comment;

        return $comment;
    }
    
    public function save_sentiment_analysis($evaluationid, $polarity, $score)
    {
        global $DB;

        $manager = new team_manager();

        //Call check function from team_manager
        //To confirm that this evaluationid exist
        if ($manager->record_exist('smartspe_evaluation', ['id' => $evaluationid]))
        {
            //Save record
            $record = new \stdClass();
            $record->sentimentscore = $score;
            $record->polarity = $polarity;

            //Insert record
            $sentimentid = $DB->insert_record('smartspe_sentiment_analysis', $record);
        }
        else 
        {
            throw new moodle_exception("db_evaluation: This evaluationid ({$evaluationid}) has not been created");
        }

        return $sentimentid;
    }

    public function get_polarity($evaluationid)
    {
        global $DB;

        //get data from db
        $record = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $evaluationid]);

        return $record->polarity;
    }

    public function get_sentiment_score($evaluationid)
    {
        global $DB;

        //get data from db
        $record = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $evaluationid]);

        return $record->sentimentscore;
    }
}

?>
