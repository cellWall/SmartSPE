<?php

namespace mod_smartspe\handler;

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

/*
*Handle submission duration rule
*
*/
class duration_controller
{
    //Start date of submission
    private $startdate;
    //End date of submission
    private $enddate;

    private $timezone;

    public function __construct($startdate, $enddate, $timezone = null)
    {
        global $DB, $USER;

        $this->startdate = $startdate;
        $this->enddate = $enddate;

        if ($this->startdate >= $this->enddate)
            throw new moodle_exception('Start date must be before end date.');

        $this->timezone = $timezone ?? $USER->timezone;
    }
    
    /*
    **
    **Check if submission currently open
    **
    **@return remaining time
    */
    public function is_submission_open()
    {
        $now = time();
        return ($now >= $this->startdate && $now <= $this->enddate);
    }

    /*
    **
    **Get time remaining
    **
    **@return remaining time
    */
    public function time_submission_remaining()
    {
        $now = time();

        //If current time more than end date
        if ($now > $this->enddate) 
            return 0;

        return $this->enddate - $now;
    }

    public function get_start_date()
    {
        return userdate($this->startdate, $this->timezone);
    }
    
    public function get_end_date()
    {
        return userdate($this->enddate, $this->timezone);
    }

}
