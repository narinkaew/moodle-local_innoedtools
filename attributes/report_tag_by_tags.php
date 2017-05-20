<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report by tags
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('report_tag_base.php');

/**
 * Report by tags
 *
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tag_by_tags extends report_tag_base {

    /**
     * Logic to generate the report
     */
    public function generate_report() {
        global $DB, $PAGE, $USER, $OUTPUT;

        // Initialise the table.
        $table = new html_table();
        $table->head = $this->generate_column_name();
        $table->class = '';
        $table->id = '';

        // Prepare table result.
        $table->align = $this->generate_column_align();
        $table->size = $this->generate_column_size();
        $table->data = array();

        // Prepare sql statement.
        $sql = $this->generate_query();
        $rows = $DB->get_records_sql($sql, $this->arrcoursesparam);

        // All tags each student.
        foreach ($rows as $row) {
            // Display data.
            $table->data[] = $this->display_row($row);
        }

        // Add a totals row.
        $table->data[] = $this->total_display_row($table->data);

        // Print it.
        echo html_writer::table($table);
    }

    /**
     * Generate query for the report
     */
    public function generate_query() {

        $sqlstudentslist = $this->generate_query_enrolled_student(true);

        // Get using tags for all courses.
        $i = 0;
        $sqlinnertagincourse = "";
        $sqlinnercoursecount = "";
        $sqlinnercoursecountsum = "";
        $alias = "col";
        foreach ($this->arrcourses as $courseid => $courseidnumber) {
            $i++;
            $this->arrcoursesparam[] = $courseidnumber;

            $sqlinnertagincourse .= "SELECT a.rawname, a.id, COUNT(a.rawname) cnt
                                    FROM 
                                    (
                                        SELECT t.rawname, c.id, p.userid
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                        JOIN {post}  p ON p.id = ti.itemid
                                        JOIN {blog_association} ba ON ba.blogid = p.id
                                        JOIN {course} c ON c.id = p.courseid
                                        WHERE (p.publishstate = 'site' OR p.publishstate='public')
                                        AND ti.itemtype = 'post'
                                        AND ti.component = 'core'
                                        AND p.courseid = $courseid
                                    ) a
                                    INNER JOIN
                                    (
                                        $sqlstudentslist
                                    ) b
                                    ON a.userid = b.userid
                                    GROUP BY a.rawname, a.id";

            $sqlinnercoursecount .= "CASE WHEN User_Items.id = $courseid THEN cnt END AS $alias$i";
            $sqlinnercoursecountsum .= "COALESCE(SUM($alias$i), 0) AS ?";

            // Not last record.
            if ($i != $this->numcourses) {
                $sqlinnertagincourse .= " UNION ";
                $sqlinnercoursecount .= ",";
                $sqlinnercoursecountsum .= ",";
            }
        }

        $sql = "SELECT rawname, $sqlinnercoursecountsum, $this->numstudent
                FROM
                (
                    SELECT User_Items.rawname, $sqlinnercoursecount
                    FROM
                    (
                        SELECT t.rawname, b.id, COALESCE(b.cnt, 0) cnt
                        FROM {tag} t
                        LEFT JOIN
                        (
                            $sqlinnertagincourse
                        ) b
                        ON t.rawname = b.rawname
                        WHERE t.isstandard = 1
                    ) User_Items
                ) User_Items_Extended
                GROUP BY rawname";

        return $sql;
    }

    /**
     * Generate column name
     */
    public function generate_column_name() {
        $col = array();

        array_push($col, get_string('attributename', 'local_innoedtools'));
        foreach ($this->arrcourses as $courseid => $courseidnumber) {
            array_push($col, $courseidnumber);
        }
        array_push($col, get_string('overall', 'local_innoedtools'));
        array_push($col, get_string('totalstudents', 'local_innoedtools'));

        return $col;
    }

    /**
     * Generate column alignment
     */
    public function generate_column_align() {
        $align = array();

        array_push($align, 'left');
        for ($i = 0; $i < $this->numcourses; $i++) {
            array_push($align, 'center');
        }
        array_push($align, 'center');
        array_push($align, 'center');

        return $align;
    }

    /**
     * Generate column width
     */
    public function generate_column_size() {
        $size = array();

        array_push($size, '25%');
        $colsize = $this->convert_to_percent((50 / 100), $this->numcourses);
        for ($i = 0; $i < $this->numcourses; $i++) {
            array_push($size, $colsize);
        }
        array_push($size, '15%');
        array_push($size, '10%');

        return $size;
    }

    /**
     * Generate row data
     *
     * @param array $row
     */
    public function display_row($row) {
        $data = array();
        $totalrow = 0;

        array_push($data, $row->rawname);
        foreach ($this->arrcourses as $courseid => $courseidnumber) {
            $c = strtolower($courseidnumber);
            array_push($data, $row->$c.' ('.$this->convert_to_percent($row->$c, $this->get_col_possibility()).')');
            $totalrow += $row->$c;
        }
        array_push($data, $this->generate_progress_bar($totalrow, $this->get_row_possibility()));
        array_push($data, $this->numstudent);

        return $data;
    }

    /**
     * Generate total row data
     *
     * @param array $table
     */
    public function total_display_row($table) {
        $sumdata = array();
        $data = array();
        $totalallrow = 0;

        // Total count, exclude for first & last columns.
        foreach ($table as $key => $value) {
            foreach ($value as $key2 => $value2) {
                if ($key2 != 0 && $key2 != count($value) - 1 && $key2 != count($value) - 2) {
                    if (!isset($data[$key2])) {
                        $data[$key2] = 0;
                    }
                    $data[$key2] = $data[$key2] + $value2;
                }
            }
        }

        array_push($sumdata, $this->aggregateicon);
        foreach ($data as $key => $value) {
            $percenttext = $this->convert_to_percent($value, $this->get_col_total_possibility());
            array_push($sumdata, '<b>'.$value.' ('.$percenttext.')'.'</b>');
            $totalallrow += $value;
        }
        array_push($sumdata, $this->generate_progress_bar($totalallrow, $this->get_total_possibility()));
        array_push($sumdata, $this->numstudent);

        return $sumdata;
    }

    /**
     * Get row possibility for denominator
     */
    public function get_row_possibility() {
        return $this->numcourses * $this->numstudent;
    }

    /**
     * Get total row possibility for denominator
     */
    public function get_total_possibility() {
        return $this->numcourses * $this->numstudent * $this->numstandardtag;
    }

    /**
     * Get column possibility for denominator of each cell data
     */
    public function get_col_possibility() {
        return $this->numstudent;
    }

    /**
     * Get total column possibility for denominator of each cell data
     */
    public function get_col_total_possibility() {
        return $this->numstudent * $this->numstandardtag;
    }
}