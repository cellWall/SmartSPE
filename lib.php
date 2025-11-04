<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Library of functions and constants for module smartspe
 *
 * @package    mod_smartspe
 * @copyright  2025 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add smartspe instance.
 *
 * Called when a new instance of the module is created.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param mod_smartspe_mod_form $mform
 * @return int new smartspe instance id
 */
function smartspe_add_instance($data, $mform) 
{
    global $DB, $COURSE;

    $instance = new stdClass();
    $instance->name = $data->name;
    $instance->course = $COURSE->id;
    $instance->startdate = $data->startdate;
    $instance->enddate = $data->enddate;
    $instance->timecreated = time();
    $instance->timemodified = time();

    $questionids = (array)$data->questionids; // ensures it's always an array
    if (is_array($data->questionids)) 
    {
        $instance->questionids = implode(',', $data->questionids);
    } 
    else 
    {
        $instance->questionids = $data->questionids; // already a string
    }


    // Insert new record into the module table.
    $id = $DB->insert_record('smartspe', $instance);

    // Return the new instance id.
    return $id;
}

/**
 * Update smartspe instance.
 *
 * Called when an existing instance is updated.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param mod_smartspe_mod_form $mform
 * @return bool true on success, false otherwise
 */
function smartspe_update_instance($data, $mform) 
{
    global $DB, $COURSE;

    $instance = new stdClass();
    $data->id = $data->instance;
    $instance->id = $data->id;
    $instance->course = $COURSE->id;
    $instance->name = $data->name;
    $instance->startdate = $data->startdate;
    $instance->enddate = $data->enddate;
    $instance->timemodified = time();

    $data->questionids = (array)$data->questionids; // ensures it's always an array
    if (is_array($data->questionids)) {
    $instance->questionids = implode(',', $data->questionids);
    } else {
        $instance->questionids = $data->questionids; // already a string
    }

    return $DB->update_record('smartspe', $instance);
}

/**
 * Delete smartspe instance.
 *
 * Called when an instance of the module is deleted.
 *
 * @param int $id ID of the module instance
 * @return bool true on success, false otherwise
 */
function smartspe_delete_instance($id)
{
    global $DB;

    // Check record exists
    if (!$DB->record_exists('smartspe', ['id' => $id])) {
        return false;
    }

    // Delete related attempts
    $DB->delete_records('smartspe_attempts', ['smartspeid' => $id]);

    // Delete main record
    $DB->delete_records('smartspe', ['id' => $id]);

    return true;
}

/**
 * Supports feature detection.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if module supports feature, null if unknown
 */
function smartspe_supports($feature) 
{
    switch ($feature) 
    {
        case FEATURE_MOD_INTRO:          return true;
        case FEATURE_SHOW_DESCRIPTION:   return true;
        case FEATURE_BACKUP_MOODLE2:     return true;
        default:                         return null;
    }
}