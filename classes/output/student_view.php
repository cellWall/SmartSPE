<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class student_view implements renderable, templatable {
    protected $quiz_manager;
    protected $evaluateeid;  // current user (self) or peer
    protected $type;          // 'self' or 'peer'
    protected $questionids;
    protected $saved_questions;

    public function __construct($quiz_manager, $evaluationid, $type, $questionids, $saved_questions = []) {
        $this->quiz_manager = $quiz_manager;
        $this->evaluateeid = $evaluationid;
        $this->type = $type;
        $this->questionids = $questionids;
        $this->saved_questions = $saved_questions;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = new stdClass();

        // Fetch questions from quiz manager
        $questionsraw = $this->quiz_manager->get_questions($this->questionids);

        $questionsdata = [];
        $displayNumber = 1;

        foreach ($questionsraw as $q) {
            $qtype = $q['qtype'] ?? 'multichoice';
            $currentAnswer = null;
            if (!empty($this->saved_questions)) 
            {
                foreach ($this->saved_questions as $saved) 
                {
                    if ($saved['id'] == $q['id']) 
                    {
                        $currentAnswer = $saved['current_answer'] ?? null;
                        break;
                    }
                }
            }

            // Prepare MCQ options
            $options = [];
            if ($qtype === 'multichoice' && !empty($q['options'])) {
                foreach ($q['options'] as $opt) {
                    $options[] = [
                        'value'   => $opt['value'],
                        'text'    => $opt['text'],
                        'checked' => ($currentAnswer !== null && $currentAnswer == $opt['value'])
                    ];
                }
            }

            $questionsdata[] = [
                'id'             => $q['id'],
                'display_number' => $displayNumber++,
                'text'           => $q['text'],
                'qtype'          => $qtype,
                'qtype_essay'    => ($qtype === 'essay'),
                'qtype_mcq'      => ($qtype === 'multichoice'),
                'options'        => $options,
                'current_answer' => $currentAnswer ?? ''
            ];
        }

        // Get all members for navigation
        $members = $this->quiz_manager->get_members();
        $member_ids = array_column($members, 'id');
        
        // Find current position
        $current_index = array_search($this->evaluateeid, $member_ids);
        $total_members = count($members);
        
        // Determine navigation
        $has_next = ($current_index !== false && $current_index < $total_members - 1);
        $has_back = ($current_index > 0);
        $is_last = ($current_index === $total_members - 1);
        
        // Get previous evaluatee ID
        $prev_evaluateeid = null;
        if ($has_back) {
            $prev_evaluateeid = $member_ids[$current_index - 1];
        }
        
        // Get evaluatee name
        $evaluatee_name = '';
        if ($this->type === 'self') {
            $evaluatee_name = 'Yourself';
        } else {
            foreach ($members as $m) {
                if ($m->id == $this->evaluateeid) {
                    $evaluatee_name = fullname($m);
                    break;
                }
            }
        }

        $data->fullname = fullname($USER);
        $data->evaluatee_name = $evaluatee_name;
        $data->questions = $questionsdata;
        $data->evaluateeid = $this->evaluateeid;
        $data->type = $this->type;
        $data->is_peer = ($this->type === 'peer');
        $data->is_self = ($this->type === 'self');
        $data->has_next = $has_next;
        $data->has_back = $has_back;
        $data->is_last = $is_last;
        $data->prev_evaluateeid = $prev_evaluateeid;
        $data->sesskey = sesskey();
        $data->cmid = $this->quiz_manager->get_cmid();
        $data->progress = ($current_index + 1) . ' of ' . $total_members;

        return $data;
    }
}
