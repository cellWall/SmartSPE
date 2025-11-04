<?php

namespace mod_smartspe\handler;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class download_handler
{
    /**
     * Download the report
     *
     * Called when teacher/Unit coordinator request download
     * 
     *@param $filename file name
     *@param $extension file extension
     * @return bool if download is successful
     */
    public function download_file($filename, $extension, $course, $details=false)
    {
        //Check the extension
        if ($extension == "csv" && $details)
            return $this->create_file_csv_details($filename.'.'.$extension, $course);
        else if ($extension == "xlsx" && !$details)
            return $this->create_file_xlsx_summary($filename.'.'.$extension, $course);
        else if ($extension == "pdf")
            return $this->create_file_pdf($filename.'.'.$extension);
        else
            throw new moodle_exception(("The file extension is not supported: {$extension}"));
    }

    /**
     * Create report for .csv
     *
     * Called when teacher/Unit coordinator request download for csv file
     * 
     *@param $filename file name
     * @return boolean if download is successful
     */
    private function create_file_csv_details($filename, $course)
    {
        global $DB;

        // Remove any output before sending CSV
        while (ob_get_level()) {
            ob_end_clean();
        }
        \core\session\manager::write_close();
        
        // Create temporary file in Moodle temp dir
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;

        // Create CSV in memory
        $fp = fopen($tempfile, 'w');
        if (!$fp) {
            throw new moodle_exception("Cannot open file stream for CSV");
        }

        $header = ["StudentID","Name", "Lastname","Memberid","Member_Name","Member_Lastname","Group","Polarity",
                    "Sentiment_Scores","Q1","Q2","Q3","Q4","Q5","Average","comment","self_comment"];

        fputcsv($fp, $header);

        $records = $DB->get_records('smartspe_evaluation', ['course' => $course]);
        foreach ($records as $record) {
            fputcsv($fp, $this->get_line_record_details($record));
        }

        fclose($fp);

        // Use Moodle’s send_file() to serve download safely
        send_file($tempfile, $filename, 0, 0, false, true, 'text/csv');

        // Stop Moodle rendering page
        exit;
    }

    private function create_file_xlsx_summary($filename, $course)
    {
        global $DB;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        // Get all teams in this course
        $teams = $DB->get_records('groups', ['courseid' => $course]);
        foreach ($teams as $team) {

            $members = $DB->get_records('groups_members', ['groupid' => $team->id]);
            if (!$members) {
                continue;
            }

            // --- Header setup ---
            $eval_header = ["", "Student being evaluated", "", ""];
            $header = ["", "Assessment Criteria", "", ""];
            $criteria = ["1", "2", "3", "4", "5", "Average", ""];

            $criteria_header = [];
            $evaluatee_header = [];
            foreach ($members as $group_member) {
                $userid = $group_member->userid;
                $member = $DB->get_record('user', ['id' => $userid]);

                $criteria_header = array_merge($criteria_header, $criteria);
                $member_header = [
                    $member->lastname . " " . $member->firstname, '', '', '', '', '', ''
                ];
                $evaluatee_header = array_merge($evaluatee_header, $member_header);
            }

            $final_header = array_merge($header, $criteria_header);
            $final_eval_header = array_merge($eval_header, $evaluatee_header);

            // Write headers
            $sheet->fromArray($final_eval_header, null, "A{$row}");
            $sheet->fromArray($final_header, null, "A" . ($row + 1));

            // Style header
            $headerRange = "A{$row}:" . $sheet->getHighestColumn() . ($row + 1);
            $sheet->getStyle($headerRange)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D9E1F2']
                ],
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);

            $row += 3;

            // Subheader for evaluator info
            $sheet->fromArray(["Team", "StudentID", "Surname", "Given Name"], null, "A{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
            $row++;

            // --- Prepare structure for vertical averaging ---
            // Store all answers keyed by [evaluateeid][question_index]
            $vertical_sums = [];
            $vertical_counts = [];

            // --- Data rows for evaluators ---
            foreach ($members as $member) {
                $userid = $member->userid;
                $records = $DB->get_records('smartspe_evaluation', ['evaluator' => $userid]);
                if (!$records) {
                    continue;
                }

                $user = $DB->get_record('user', ['id' => $userid]);
                $group_name = $team->name ?? '';

                $details = [$group_name, $userid, $user->lastname ?? '', $user->firstname ?? ''];
                $result_line = [];

                foreach ($records as $record) {
                    $result = $this->get_line_summary($record); // returns [Q1..Q5, avg]
                    $result_line = array_merge($result_line, $result);

                    // Track vertical sums
                    $evaluatee = $record->evaluatee;
                    foreach ($result as $index => $val) {
                        if (!is_numeric($val)) {
                            continue;
                        }
                        $vertical_sums[$evaluatee][$index] = ($vertical_sums[$evaluatee][$index] ?? 0) + $val;
                        $vertical_counts[$evaluatee][$index] = ($vertical_counts[$evaluatee][$index] ?? 0) + 1;
                    }
                }

                $sheet->fromArray(array_merge($details, $result_line), null, "A{$row}");
                $row++;
            }

            // --- Add Average Row ---
            $avg_details = ["", "", "", "Average"];
            $avg_line = [];

            foreach ($members as $group_member) {
                $evaluatee = $group_member->userid;
                $answers = [];

                if (isset($vertical_sums[$evaluatee])) {
                    foreach ($vertical_sums[$evaluatee] as $index => $sum) {
                        $count = $vertical_counts[$evaluatee][$index] ?? 0;
                        $avg = $count ? round($sum / $count, 2) : '';
                        $answers[] = $avg;
                    }
                }

                // Ensure always 6 columns (5Q + avg)
                $answers = array_pad($answers, 6, '');

                // Add trailing empty to match header layout
                $answers[] = '';

                $avg_line = array_merge($avg_line, $answers);
            }

            // Write the averages row
            $sheet->fromArray(array_merge($avg_details, $avg_line), null, "A{$row}");
            $sheet->getStyle("A{$row}:" . $sheet->getHighestColumn() . "{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFF2CC']
                ],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);

            $row += 3; // Leave space between teams
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temporary file
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempfile);

        // Send file to browser
        send_file(
            $tempfile,
            $filename,
            0,
            0,
            false,
            true,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        exit;
    }


    private function create_file_pdf($filename)
    {
        global $DB;

        return true;
    }

    /**
     *Helper in splitting data into columns
     * 
     *@param $record record of evaluation
     * @return array of data
     */
    private function get_line_record_details($record)
    {
        global $DB;

        //User
        $userid = $record->evaluator; //Get evalutor id
        $user = $DB->get_record('user', ['id' => $userid]); //Get member name
        $name = $user->firstname ?? '';
        $lastname = $user->lastname ?? '';

        //Member
        $memberid = $record->evaluatee; //Get evalutee id
        $member = $DB->get_record('user', ['id' => $memberid]); //Get member name
        $member_name = $member->firstname ?? '';
        $member_lastname = $member->lastname ?? '';

        //Groups
        $group_member = $DB->get_record('groups_members', ['userid' => $userid]); //get teamid
        $group = $DB->get_record('groups', ['id' => $group_member->groupid]);
        $group_name = $group->name ?? '';

        //Get analysis result
        $result = $DB->get_record('feedback_ai_results', ['evaluatorID' => $userid, 'evaluateeID' => $memberid]);
        $polarity = $result->predicted_label ?? null;
        $sentiment_score = $result->text_score ?? null;
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float)$record->average : null;
        $comment = $record->comment ?? null;
        $self_comment = $record->self_comment ?? null;

        $line = [$userid,$name, $lastname,$memberid,$member_name, $member_lastname,$group_name,$polarity,
                $sentiment_score,$q1,$q2,$q3,$q4,$q5,$average,$comment,$self_comment];

        return $line;
    }

    private function get_line_summary($record)
    {
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float)$record->average : null;

        $line = [$q1,$q2,$q3,$q4,$q5,$average, ""];

        return $line;
    }
}
