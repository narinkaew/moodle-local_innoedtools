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
 * Report by students
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('report_tag_base.php');

/**
 * Report by students
 *
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tag_by_students extends report_tag_base {

    /**
     * Constructor
     */
	public function __construct() {
        parent::__construct();
	}

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

        // Prepare table result
        $table->align = $this->generate_column_align();
        $table->size = $this->generate_column_size();
        $table->data = array();

        // Prepare sql statement
        $sql = $this->generate_query();
        $rows = $DB->get_records_sql($sql);

        // All tags each student
        foreach ($rows as $row) {
            // display data
            $table->data[] = $this->display_row($row);
        }

        // Add a totals row
        $table->data[] = $this->total_display_row($table->data);

        // Print it
        echo html_writer::table($table);
    }

    /**
     * Generate query for the report
     */
	public function generate_query() {
        // Get using tags for all courses
        $i = 0;
        $sql_inner_tag_in_course = "";
        $sql_inner_course_count = "";
        $sql_inner_course_count_sum = "";
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
            $i++;

            $sql_inner_tag_in_course .= "   SELECT p.userid, c.id, COUNT(p.userid) cnt
                                            FROM {tag} t
                                            JOIN {tag_instance} ti ON ti.tagid = t.id
                                            JOIN {post}  p ON p.id = ti.itemid
                                            JOIN {blog_association} ba ON ba.blogid = p.id
                                            JOIN {course} c ON c.id = p.courseid
                                            WHERE (p.publishstate = 'site' OR p.publishstate='public')
                                            AND ti.itemtype = 'post'
                                            AND ti.component = 'core'
                                            AND p.courseid = $course_id
                                            AND t.isstandard = 1
                                            GROUP BY p.userid, c.id
                                        ";

            $sql_inner_course_count .= "CASE WHEN User_Items.courseid = $course_id THEN cnt END AS $course_idnumber";
            $sql_inner_course_count_sum .= "COALESCE(SUM($course_idnumber), 0) AS $course_idnumber";

            // Not last record
            if($i != $this->num_courses) {
                $sql_inner_tag_in_course .= " UNION ";
                $sql_inner_course_count .= ",";
                $sql_inner_course_count_sum .= ",";
            }
        }

        $sql = "SELECT  fullname,
                        $sql_inner_course_count_sum
                FROM
                (
                    SELECT  User_Items.userid,
                            User_Items.firstname,
                            User_Items.lastname,
                            User_Items.fullname,
                            $sql_inner_course_count
                    FROM
                    (
                        SELECT u.userid, u.firstname, u.lastname, u.fullname, b.id courseid, COALESCE(b.cnt, 0) cnt
                        FROM
                        (
                            SELECT u.id userid, u.firstname, u.lastname, CONCAT(u.firstname, ' ', u.lastname) fullname
                            FROM {role_assignments} ra
                            INNER JOIN {role} r ON r.id = ra.roleid
                            INNER JOIN {user} u ON u.id = ra.userid
                            WHERE ra.contextid = $this->context_course_id
                            AND archetype = 'student'
                            AND u.deleted = 0
                        ) u
                        LEFT JOIN
                        (
                            $sql_inner_tag_in_course
                        ) b
                        ON u.userid = b.userid
                    ) User_Items
                ) User_Items_Extended
                GROUP BY userid
                ORDER BY firstname, lastname
            ";

        return $sql;
	}

    /**
     * Generate column name
     */
	public function generate_column_name() {
		$col = array();

        array_push($col, get_string('username', 'local_innoedtools'));
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
            array_push($col, $course_idnumber);
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
        for ($i=0; $i < $this->num_courses; $i++) { 
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

        $colSize = $this->convert_to_percent((50/100), $this->num_courses);
        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($size, $colSize); 
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
        $total_row = 0;

		array_push($data, $row->fullname);
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
        	$c = strtolower($course_idnumber);
            array_push($data, $row->$c); 
            $total_row += $row->$c;
        }
        array_push($data, $this->generate_progress_bar($total_row, $this->get_row_possibility()));

        return $data;
	}

    /**
     * Generate total row data
     *
     * @param array $table
     */
	public function total_display_row($table) {
		$sum_data = array();
		$data = array();
        $total_all_row = 0;

		// total count, exclude for first & last columns
		foreach ($table as $key => $value) {
			foreach ($value as $key2 => $value2) {
				if($key2 != 0 && $key2 != count($value)-1) {
					$data[$key2] = $data[$key2] + $value2;
				}
			}
		}

		array_push($sum_data, $this->aggregate_icon);
        foreach ($data as $key => $value) {
            array_push($sum_data, '<b>'.$value.'</b>');
            $total_all_row += $value;
        }
        array_push($sum_data, $this->generate_progress_bar($total_all_row, $this->get_total_possibility()));

		return $sum_data;
	}

    /**
     * Get row possibility for denominator
     */
    public function get_row_possibility() {
        return $this->num_courses * $this->num_standard_tag;
    }

    /**
     * Get total row possibility for denominator
     */
    public function get_total_possibility() {
        return $this->num_courses * $this->num_standard_tag * $this->num_student;
    }
}