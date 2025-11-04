<?php
namespace mod_smartspe;

require_once(__DIR__ . '/../../../config.php');

use core\exception\moodle_exception;

class db_team_manager
{
    public function get_members_id($userid, $courseid)
    {
        global $DB;

        if (!$this->record_exist('groups_members', ['userid' => $userid])) 
        {
            // returning empty array if user is not in any gorup insstead of throwing an exception
            return [];
        }

        // Get the group record of this user.
        $user = $DB->get_record('groups_members', ['userid' => $userid], '*', MUST_EXIST);
        $groupid = $user->groupid;

        // Ensure the group belongs to this course.
        if (!$this->record_exist('groups', ['id' => $groupid, 'courseid' => $courseid])) 
        {
            throw new moodle_exception("User {$userid}’s group does not belong to course {$courseid}.");
        }

        // Get all members in the same group.
        $members = $DB->get_records('groups_members', ['groupid' => $groupid], '', 'userid');

        if (empty($members)) {
            throw new moodle_exception("No members found in the group for user {$userid}.");
        }

        $members_id = array_map(fn($m) => $m->userid, $members);

        return $members_id;
    }

    public function record_exist($table, $record)
    {
        global $DB;

        //$record should be array ['column' => 'value']
        return $DB->record_exists($table, $record);
    }
}