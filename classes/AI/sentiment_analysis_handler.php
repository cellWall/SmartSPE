<?php

namespace mod_smartspe\AI;

use mod_smartspe\db_evaluation;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

//This class carries sentiment analysis process

class sentiment_analysis_handler
{
    private $script_dir;

    public function __construct($script_dir) 
    {
        $this->script_dir = $script_dir;
    }

    /**
     * Feed data into AI model and get polarity and score
     * 
     *@param $evaluationid process through each evaluationid
     * @return boolean if download is successful
     */
    public function process_sentiment_analysis($evaluationid)
    {
        global $DB;
        //collect records all records
        $record = $DB->get_record('smartspe_evaluation', ['evaluationid' => $evaluationid]);
        $comment = $record->comment;
        $self_comment = $record->self_comment;

        //crete AI model

        //Feed comment and self comment into AI

        //Get AI result
        $polarity = "Positive";
        $score = "4.5";

        //Save polarity and score
        $db_manager = new db_evaluation();
        $sentimentid = $db_manager->save_sentiment_analysis($evaluationid, $polarity, $score);

        return $sentimentid;
    }

    public function predict($input) 
    {
        $python = 'python3'; // adjust path if necessary
        $predict_script = escapeshellarg($this->script_dir . '/predict.py');
        $input_arg = escapeshellarg($input);

        $command = "$python $predict_script $input_arg";
        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \Exception('AI prediction failed');
        }

        $result = json_decode(implode("\n", $output), true);
        return $result['prediction'] ?? null;
    }
}