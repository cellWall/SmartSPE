<?php

namespace mod_smartspe\handler;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');

class questions_handler
{
    /**
     * Get questions from questions bank
     *
     * Called when loading data and display questions to users.
     *
     * @param $data the data getting from mod_smartspe_mod_form
     * @return array $questions
     */
    public function get_all_questions($questionids)
    {

        // $data comes from $mform->get_data() after submission
        if (empty($questionids) || !is_array($questionids))
            return [];

        // Format results as array
        $questions = [];
        foreach ($questionids as $q) 
        {
            // Load the full question object
            $questionobj = \question_bank::load_question($q);

            $options = [];
            if ($questionobj->qtype->name() === 'multichoice') 
            {
                foreach ($questionobj->answers as $answer) 
                {
                    $options[] = [
                        'value' => $answer->id,
                        'text'  => $answer->answer
                    ];
                }
            }
            $questions[] = 
            [
                'id' => $questionobj->id,
                'name' => $questionobj->name,
                'text' => format_text($questionobj->questiontext, $questionobj->questiontextformat),
                'qtype' => $questionobj->qtype->name(),
                'defaultmark' => $questionobj->defaultmark,
                'questiontextformat' => $questionobj->questiontextformat,
                'options' => $options
                //'answers' => $questionobj->answers ? array_values($questionobj->answers) : []
            ];
        }

        return $questions;
    }

    /**
     * Add questions into question bank using question id
     *
     * Called after the mod_form is created and teacher selected questions.
     *
     * @param $context add questions to specific context
     * @param $attemptid the current attemptid
     * @param $data the data getting from mod_smartspe_mod_form
     * @return $quba
     */
    public function add_all_questions($context, $questionids, $attemptid)
    {
        global $DB;

        $quba = \question_engine::make_questions_usage_by_activity('mod_smartspe', $context);
        $quba->set_preferred_behaviour('deferredfeedback');

        // $data comes from $mform->get_data() after submission
        if (empty($questionids))
            return [];

        $qids = $questionids;
        
        foreach ($qids as $q)
        {
            $question = \question_bank::load_question($q);
            $qa = $quba->add_question($question);
        }

        //Save the usage
        $quba->start_all_questions();
        \question_engine::save_questions_usage_by_activity($quba);

        $qubaid = $quba->get_id(); // usage ID after saving
        $DB->set_field('smartspe_attempts', 'uniqueid', $qubaid, ['id' => $attemptid]);

        return $quba;
    }
}