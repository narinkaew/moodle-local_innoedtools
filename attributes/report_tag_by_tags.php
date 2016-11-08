<?php
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
 * report by tags
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('report_tag_base.php');

class report_tag_by_tags extends report_tag_base {

	public function __construct() {
        parent::__construct();
	}

    public function generate_report() {
        global $DB, $PAGE, $USER, $OUTPUT;
        
        // Initialise the table.
        $table = new html_table();
        $table->head = $this->generate_column_name();
        $table->class = '';
        $table->id = '';

        /*** Prepare table result ***/
        $table->align = $this->generate_column_align();
        $table->size = $this->generate_column_size();
        $table->data = array();

        /*** Prepare sql statement ***/
        $sql = $this->generate_query();
        $rows = $DB->get_records_sql($sql);

        /*** All tags each student ***/
        foreach ($rows as $row) {
            /*** display data ***/
            $table->data[] = $this->display_row($row);
        }

        /*** Add a totals row. ***/
        $table->data[] = $this->total_display_row($table->data);

        // Print it.
        echo html_writer::table($table);
    }

	public function generate_query() {

        $sql_students_list = $this->generate_query_enrolled_student(true);

        /*** Get using tags for all courses ***/
        $i = 0;
        $sql_inner_tag_in_course = "";
        $sql_inner_course_count = "";
        $sql_inner_course_count_sum = "";
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
            $i++;

            $sql_inner_tag_in_course .= "   SELECT a.rawname, a.id, COUNT(a.rawname) cnt 
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
                                                AND p.courseid = $course_id
                                            ) a
                                            INNER JOIN
                                            (
                                                $sql_students_list
                                            ) b
                                            ON a.userid = b.userid
                                            GROUP BY a.rawname, a.id 
                                        ";

            $sql_inner_course_count .= "CASE WHEN User_Items.id = $course_id THEN cnt END AS $course_idnumber";
            $sql_inner_course_count_sum .= "COALESCE(SUM($course_idnumber), 0) AS $course_idnumber";

            /*** Not last record ***/
            if($i != $this->num_courses) {
                $sql_inner_tag_in_course .= " UNION ";
                $sql_inner_course_count .= ",";
                $sql_inner_course_count_sum .= ",";
            }
        }

        $sql = "SELECT  rawname,
                        $sql_inner_course_count_sum,
                        $this->num_student
                FROM
                (
                    SELECT  User_Items.rawname,
                            $sql_inner_course_count
                    FROM
                    (
                        SELECT t.rawname, b.id, COALESCE(b.cnt, 0) cnt
                        FROM {tag} t
                        LEFT JOIN
                        (
                            $sql_inner_tag_in_course
                        ) b
                        ON t.rawname = b.rawname
                        WHERE t.isstandard = 1
                    ) User_Items
                ) User_Items_Extended
                GROUP BY rawname
            ";

        return $sql;
	}

	public function generate_column_name() {
		$col = array();

        array_push($col, get_string('attributename', 'local_innoedtools'));
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
            array_push($col, $course_idnumber);
        }
        array_push($col, get_string('overall', 'local_innoedtools'));
        array_push($col, get_string('totalstudents', 'local_innoedtools'));

        return $col;
	}

	public function generate_column_align() {
		$align = array();

        array_push($align, 'left');
        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($align, 'center'); 
        }
        array_push($align, 'center');
        array_push($align, 'center'); 

        return $align;
	}

	public function generate_column_size() {
		$size = array();

        array_push($size, '25%');
        $colSize = $this->convert_to_percent((50/100), $this->num_courses);
        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($size, $colSize); 
        }
        array_push($size, '15%');
        array_push($size, '10%');

        return $size;
	}

	public function display_row($row) {
		$data = array();
        $total_row = 0;

		array_push($data, $row->rawname);
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
        	$c = strtolower($course_idnumber);
            array_push($data, $row->$c.' ('.$this->convert_to_percent($row->$c, $this->get_col_possibility()).')'); 
            $total_row += $row->$c;
        }
        array_push($data, $this->generate_progress_bar($total_row, $this->get_row_possibility()));
        array_push($data, $this->num_student);

        return $data;
	}

	public function total_display_row($table) {
		$sum_data = array();
		$data = array();
        $total_all_row = 0;

		// total count, exclude for first & last columns
		foreach ($table as $key => $value) {
			foreach ($value as $key2 => $value2) {
				if($key2 != 0 && $key2 != count($value)-1 && $key2 != count($value)-2) {
					$data[$key2] = $data[$key2] + $value2;
				}
			}
		}

		array_push($sum_data, $this->aggregate_icon);
        foreach ($data as $key => $value) {
            array_push($sum_data, '<b>'.$value.' ('.$this->convert_to_percent($value, $this->get_col_total_possibility()).')'.'</b>');
            $total_all_row += $value;
        }
        array_push($sum_data, $this->generate_progress_bar($total_all_row, $this->get_total_possibility()));
        array_push($sum_data, $this->num_student);

		return $sum_data;
	}

    public function get_row_possibility() {
        return $this->num_courses * $this->num_student;
    }

    public function get_total_possibility() {
        return $this->num_courses * $this->num_student * $this->num_standard_tag;
    }

    public function get_col_possibility() {
        return $this->num_student;
    }

    public function get_col_total_possibility() {
        return $this->num_student * $this->num_standard_tag;
    }
}