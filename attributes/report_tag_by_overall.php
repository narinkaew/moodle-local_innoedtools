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
 * Report overall
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('report_tag_base.php');

/**
 * Report overall
 *
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tag_by_overall extends report_tag_base {

    /**
     * @var bool Print on pdf
     * @protected
     */
    protected $ispdf = false;

    /**
     * Constructor
     *
     * @param boolean $isviewall
     */
    public function __construct($isviewall = true) {
        parent::__construct($isviewall);
    }

    /**
     * Logic to generate the report
     *
     * @param boolean $ispdf
     */
    public function generate_report($ispdf = false) {
        global $DB, $PAGE, $USER, $OUTPUT;

        $this->ispdf = $ispdf;

        // Initialise the table.
        $table = new html_table();
        $table->head = $this->generate_column_name();
        $table->class = '';
        $table->id = '';

        if ($this->numstudent == 0) {
            if (!$this->ispdf) {
                echo $OUTPUT->notification(get_string('noenrolleduser', 'local_innoedtools'), 'notifymessage');
            }
        }
        foreach ($this->arrstudents as $userid => $fullname) {
            if (!$this->ispdf) {
                $studentnamelink = html_writer::link(new moodle_url('/user/view.php', array('id' => $userid)), $fullname);
                echo $OUTPUT->heading($studentnamelink);
            }

            // Prepare table result.
            $table->align = $this->generate_column_align();
            $table->size = $this->generate_column_size();
            $table->data = array();

            // Prepare sql statement.
            $sql = $this->generate_query($userid);
            $rows = $DB->get_records_sql($sql);

            // All tags each student.
            if ($this->ispdf) {
                $table->data[] = $this->display_line_row();
            }
            foreach ($rows as $row) {
                // Display data.
                $table->data[] = $this->display_row($row);
            }

            // Add a totals row.
            if ($this->ispdf) {
                $table->data[] = $this->display_line_row();
            }
            $table->data[] = $this->total_display_row($table->data);

            // Print it.
            if (!$this->ispdf) {
                echo html_writer::table($table);
            } else {
                return $table;
            }
        }
    }

    /**
     * Export the report into pdf
     */
    public function generate_report_pdf() {
        return $this->generate_report(true);
    }

    /**
     * Generate query for the report
     *
     * @param boolean $userid Filter this parameter if current user is student capability.
     */
    public function generate_query($userid) {
        // Get using tags for all courses.
        $i = 0;
        $sqlinnertagincourse = "";
        $sqlinnercoursecount = "";
        $sqlinnercoursecountsum = "";
        $alias = "col";
        foreach ($this->arrcourses as $courseid => $courseidnumber) {
            $i++;
            //$this->arrcoursesparam[] = $courseidnumber;

            $sqlinnertagincourse .= "SELECT t.rawname,
                                            c.id,
                                            COUNT(t.rawname) cnt
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                        JOIN {post}  p ON p.id = ti.itemid
                                        JOIN {blog_association} ba ON ba.blogid = p.id
                                        JOIN {course} c ON c.id = p.courseid
                                        WHERE (p.publishstate = 'site' OR p.publishstate='public')
                                        AND ti.itemtype = 'post'
                                        AND ti.component = 'core'
                                        AND p.courseid = $courseid
                                        AND p.userid = $userid
                                        GROUP BY t.rawname, c.id";

            $sqlinnercoursecount .= "CASE WHEN User_Items.id = $courseid THEN cnt END AS $alias$i";
            $sqlinnercoursecountsum .= "COALESCE(SUM($alias$i), 0) AS \"$courseidnumber\"";

            // Not last record.
            if ($i != $this->numcourses) {
                $sqlinnertagincourse .= " UNION ";
                $sqlinnercoursecount .= ",";
                $sqlinnercoursecountsum .= ",";
            }
        }

        $sql = "SELECT rawname,
                       $sqlinnercoursecountsum
                   FROM
                    (
                        SELECT User_Items.rawname,
                               $sqlinnercoursecount
                            FROM
                            (
                                SELECT t.rawname,
                                       b.id,
                                       COALESCE(b.cnt, 0) cnt
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

        return $align;
    }

    /**
     * Generate column width
     */
    public function generate_column_size() {
        $size = array();

        array_push($size, '35%');
        $colsize = $this->convert_to_percent((50 / 100), $this->numcourses);
        for ($i = 0; $i < $this->numcourses; $i++) {
            array_push($size, $colsize);
        }
        array_push($size, '15%');

        return $size;
    }

    /**
     * Generate row data
     *
     * @param array $row
     */
    public function display_row($row) {
        $data = array();
        $countthistag = 0;

        array_push($data, $row->rawname);

        foreach ($this->arrcourses as $courseid => $courseidnumber) {
            $c = $this->get_strtolower_from_dbtype($courseidnumber);
            array_push($data, $row->$c);
            $countthistag += $row->$c;
        }

        // Progress bar.
        if (!$this->ispdf) {
            array_push($data, $this->generate_progress_bar($countthistag, $this->get_row_possibility()));
        } else {
            array_push($data, $this->convert_to_percent($countthistag, $this->get_row_possibility()));
        }

        return $data;
    }

    /**
     * Generate total row data
     *
     * @param array $table
     */
    public function total_display_row($table) {
        global $OUTPUT;

        $sumdata = array();
        $data = array();
        $totalcountthistag = 0;

        // Total count, exclude for first & last columns.
        foreach ($table as $key => $value) {
            foreach ($value as $key2 => $value2) {
                if ($key2 != 0 && $key2 != count($value) - 1) {
                    if (!isset($data[$key2])) {
                        $data[$key2] = 0;
                    }
                    $data[$key2] = $data[$key2] + $value2;
                }
            }
        }

        // Aggregate icon.
        if (!$this->ispdf) {
            array_push($sumdata, $this->aggregateicon);
        } else {
            array_push($sumdata, '<dd><b>'.get_string('total', 'local_innoedtools').'</b></dd>');
        }

        foreach ($data as $key => $value) {
            array_push($sumdata, '<b>'.$value.'</b>');
            $totalcountthistag += $value;
        }

        // Progress bar.
        if (!$this->ispdf) {
            array_push($sumdata, $this->generate_progress_bar($totalcountthistag, $this->get_total_possibility()));
        } else {
            array_push($sumdata, $this->convert_to_percent($totalcountthistag, $this->get_total_possibility()));
        }

        return $sumdata;
    }

    /**
     * Get row possibility for denominator
     */
    public function get_row_possibility() {
        return $this->numcourses;
    }

    /**
     * Get total row possibility for denominator
     */
    public function get_total_possibility() {
        return $this->numcourses * $this->numstandardtag;
    }

    /**
     * Display empty row
     */
    public function display_empty_row() {
        $data = array();

        array_push($data, null);

        for ($i = 0; $i < $this->numcourses; $i++) {
            array_push($data, null);
        }

        array_push($data, null);

        return $data;
    }

    /**
     * Display row of line
     */
    public function display_line_row() {
        $data = array();

        $line = '<hr />';
        array_push($data, $line);

        for ($i = 0; $i < $this->numcourses; $i++) {
            array_push($data, $line);
        }

        array_push($data, $line);

        return $data;
    }
}