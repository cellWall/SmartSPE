<?php
namespace mod_smartspe\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {
    public function render_student_view(student_view $page) {
        return $this->render_from_template('mod_smartspe/student_view', $page->export_for_template($this));
    }

    public function render_teacher_view(teacher_view $page) {
        return $this->render_from_template('mod_smartspe/teacher_view', $page->export_for_template($this));
    }
}
