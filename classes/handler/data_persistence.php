<?php

namespace mod_smartspe\handler;

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');

class data_persistence
{
    protected $attemptid;
    protected $attempt;
    protected $memberid;

    /**
     * Taking care of loading saved answers of specific question
     * 
     *@param $attemptid the current attempt student evaluating
     *@param $member the current member being evaluated
     * @return void
     */
    public function __construct($attemptid, $memberid)
    {
        global $DB;

        $this->attemptid = $attemptid;
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $attemptid], '*', MUST_EXIST);

        //Verify qudaid exist in question_usage
        $record = $DB->get_record('question_usages', ['id' => $this->attempt->uniqueid]);
        if(!$record)
            throw new moodle_exception("In data persistence class: usage id does not exist {$this->attempt->uniqueid}");
        
        $this->memberid = $memberid;
    }

    /**
     * Load questions usage with current state and current answers
     *
     * Called when student attempting the quiz.
     *
     * @return array $questions
     */
    public function load_attempt_questions() 
    {
        global $DB;

        // Load all questions and their current state
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        //Get comment
        // Load comment from your plugin table
        $record = $DB->get_record('smartspe_attempts', ['id' => $this->attemptid]);

        //If the comment exist
        if($record->comment)
            $comments = json_decode($record->comment, true);
        else
            $comments = null;

        //Get all questions
        $questions = [];
        foreach ($quba->get_slots() as $slot) 
        {
            $qa = $quba->get_question_attempt($slot); //get qa
            $question = $qa->get_question(); //get question of this slot
            $qtype = $question->qtype->name();
            $last_saved = $qa->get_last_qt_data(); //get saved answer, array($string)
            $currentdata = null;

            switch ($qtype) 
            {
                case 'multichoice':
                case 'truefalse':
                    $currentdata = $last_saved['answer'] ?? null;
                    break;

                case 'essay':
                    $currentdata = $comments; //comments['comment', 'self_comment']
                    break;

                default:
                    $currentdata = json_encode($currentdata); // fallback: keep full structure
                    break;
            }

            $questions[] = //questions[index][attribute]
            [
                'id' => $question->id,
                'name' => $question->name,
                'text' => $question->questiontext,
                'qtype' => $qtype,
                'state' => $qa->get_state()->__toString(),
                'current_answer' => $currentdata
            ];
        }

        return $questions;
    }

    /**
     * Auto save answers
     *
     * Called when student attempting the quiz.
     * 
     * Called in quiz_manager
     *
     * @param $newdata new answer to be saved
     * @return boolean
     */
    public function auto_save($newdata=null)
    {
        global $DB;

        // Load all questions and their current state
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        if (!empty($newdata['comment'])) 
        {
            $comments = [
                'comment' => $newdata['comment'] ?? null,
                'self_comment' => $newdata['self_comment'] ?? null
            ];

            //Save comment into attempt db
            $DB->set_field(
                'smartspe_attempts',
                'comment',
                json_encode($comments, JSON_UNESCAPED_UNICODE),
                ['id' => $this->attemptid]
            );
        }

        $answer_index = 0; //track on number of answer
        $answers = $newdata['answers'];
        if (!$answers)
            throw new moodle_exception('In data_persistence: $answers empty');

        // Loop through all slots in this usage
        foreach ($quba->get_slots() as $index => $slot)
        {
            $qa = $quba->get_question_attempt($slot);
            $question = $qa->get_question(); //get question of this slot
            $qtype = $question->qtype->name(); //get question type

            //Check question slot type
            if ($qtype === 'multichoice')
            {
                //if new data is not null
                if (isset($answers[$answer_index]))
                {
                    // Wrap the answer as an array expected by process_autosave
                    $formatteddata = ['answer' => $answers[$answer_index]];
                    //Update new data
                    $this->update_attempt_answers($slot, $formatteddata);
                    $answer_index++;
                }
                else //If no new data added
                {
                    $currentdata = $qa->get_last_qt_data();

                    //if the question has a saved data
                    if($currentdata)
                        $quba->process_action($slot, $currentdata, time());
                    else //if no saved data, leave it blank
                        $quba->process_action($slot, [], time());

                    // Update time modified
                    $DB->set_field('smartspe_attempts', 'timemodified', 
                                    time(), ['id' => $this->attemptid]);

                    $answer_index++;
                }
            }
            else if ($qtype === 'essay')
            {
                //No update for comment
                //As it already stores in smartspe_attempt
                $quba->process_action($slot, [], time()); // Update blank

                // Update time modified
                $DB->set_field('smartspe_attempts', 'timemodified', 
                                time(), ['id' => $this->attemptid]);
            }
            else
            {
                throw new moodle_exception("$qtype is not currently supported in this plugin");
            }
        }

        // Save the updated usage
        \question_engine::save_questions_usage_by_activity($quba);

        return true;
    }

    /**
     * Update new answer to specific slot question usage and auto save it
     *
     * Called when student attempting the quiz.
     * Called in quiz_manager
     *
     * @param $slot slot of question usage
     * @param $newdata new answer to be saved
     * @return boolean
     */
    private function update_attempt_answers($slot, $newdata)
    {
        global $DB;
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        $qa = $quba->get_question_attempt($slot);

        if (!$qa) {
            throw new moodle_exception("Question slot {$slot} not found in this attempt.");
        }

        //Process the update and save new data
        $updated = $qa->process_autosave($newdata);

        if (!empty($updated['error'])) 
        {
            // Handle validation errors
            throw new moodle_exception("Updated data is invalid.");
        } 
        else 
        {
            //Update time modified
            $DB->set_field('smartspe_attempts', 'timemodified', time(), ['id' => $this->attemptid]);
            // Save the updated quba
            \question_engine::save_questions_usage_by_activity($quba);
        }

        return true;
    }

    
    /**
     * Update finish state of the quiz
     *
     * Called after student has submitted the quiz
     * Called in quiz_manager
     *
     * @return boolean
     */
    public function finish_attempt()
    {
        global $DB;

        // Load the question usage for this attempt
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        // Mark all questions as finished
        $quba->finish_all_questions();

        // Save the updated question usage
        \question_engine::save_questions_usage_by_activity($quba);

        // Update the attempt record to finished
        $DB->set_field('smartspe_attempts', 'state', 'finished', ['id' => $this->attemptid]);
        $DB->set_field('smartspe_attempts', 'timemodified', time(), ['id' => $this->attemptid]);

        // Reload attempt
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->attemptid], '*', MUST_EXIST);

        return true;
    }

    public function get_slots()
    {
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        return $quba->get_slots();
    }


}