<?php

use mod_smartspe\handler\duration_controller;
use core\exception\moodle_exception;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Form for creating or editing a SmartSpe activity
 *
 * @package    mod_smartspe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_smartspe_mod_form extends moodleform_mod
{

    public function definition() 
    {

        $mform = $this->_form; // mform object is mooodle's form builder

        // Activity name.
        $mform->addElement('text', 'name', get_string('smartspe_name', 'mod_smartspe'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'smartspe_name', 'mod_smartspe');

        // Intro / description.
        $this->standard_intro_elements(get_string('smartspe_intro', 'mod_smartspe'));

        // Teacher choose question name
        $mform->addElement('autocomplete', 'questionids', get_string('selectquestion', 'mod_smartspe'), $this->get_question_options());
        $mform->setType('questionids', PARAM_SEQUENCE);
        $mform->getElement('questionids')->setMultiple(true);

        // --- Submission period section ---
        $mform->addElement('header', 'timinghdr', get_string('submissionperiod', 'mod_smartspe'));

        // Start date.
        $mform->addElement('date_time_selector', 'startdate', get_string('submissionstart', 'mod_smartspe'), ['optional' => false]);
        $mform->setDefault('startdate', time());

        // End date (deadline).
        $mform->addElement('date_time_selector', 'enddate', get_string('submissionend', 'mod_smartspe'), ['optional' => false]);
        $mform->setDefault('enddate', time() + 7 * 24 * 60 * 60); // default 1 week later

        // Standard course module elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Extra validation to ensure start date < end date.
     */
    public function validation($data, $files) 
    {
        $errors = parent::validation($data, $files);

        // Check using your duration_controller.
        try 
        {
            $duration = new duration_controller
            (
                $data['startdate'],
                $data['enddate']
            );

        } 
        catch (moodle_exception $e) 
        {
            $errors['startdate'] = $e->getMessage();
            $errors['enddate'] = $e->getMessage();
        }

        return $errors;
    }

    private function get_question_options()
    {
        global $DB, $COURSE;

        $options = [0 => get_string('choose', 'mod_smartspe')];

        // Get question categories for this course
        $context = \context_course::instance($COURSE->id);
        $categories = $DB->get_records('question_categories', ['contextid' => $context->id]);

        if (empty($categories)) {
            return $options;
        }

        // Get all category IDs
        $catids = array_keys($categories);
        list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);

        // Join new question bank tables
        $sql = "
        SELECT q.id, q.name
        FROM {question} q
        JOIN {question_versions} v ON v.questionid = q.id
        JOIN {question_bank_entries} e ON e.id = v.questionbankentryid
        WHERE e.questioncategoryid $insql
        ORDER BY q.name ASC";

        $questions = $DB->get_records_sql($sql, $params);

        foreach ($questions as $q) {
            $options[$q->id] = $q->name ?: 'No name (' . $q->id . ')';
        }

        return $options;
    }

}
