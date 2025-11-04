<?php
namespace mod_smartspe\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class main implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->activityname = 'Smart Self & Peer Evaluation';
        $data->description = 'Evaluate your groupmates based on contribution and teamwork.';
        $data->questions = [
            ['id' => 1, 'text' => 'Participated actively in discussions'],
            ['id' => 2, 'text' => 'Completed assigned tasks on time'],
            ['id' => 3, 'text' => 'Communicated effectively within the group'],
            ['id' => 4, 'text' => 'Contributed innovative ideas'],
            ['id' => 5, 'text' => 'Showed leadership and initiative']
        ];
        return $data;
    }
}
