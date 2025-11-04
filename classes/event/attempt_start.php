<?php
namespace mod_smartspe\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user starts an attempt.
 *
 * @package    mod_smartspe
 */
class attempt_start extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'smartspe';
        $this->data['crud'] = 'c'; // create
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('event_attempt_start', 'mod_smartspe');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' started an attempt (id: {$this->objectid}) in the smartspe activity (cmid: {$this->contextinstanceid}).";
    }

    public function get_url() {
        return new \moodle_url('/mod/smartspe/view.php', ['id' => $this->contextinstanceid]);
    }
}
