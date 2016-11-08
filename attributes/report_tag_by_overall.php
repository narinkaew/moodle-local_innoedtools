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
 * report overall
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('report_tag_base.php');

class report_tag_by_overall extends report_tag_base {

    protected $isPdf = false;

	public function __construct($isViewAll = true) {
        parent::__construct($isViewAll);
	}

    public function generate_report($isPdf = false) {
        global $DB, $PAGE, $USER, $OUTPUT;

        $this->isPdf = $isPdf;

        // Initialise the table.
        $table = new html_table();
        $table->head = $this->generate_column_name();
        $table->class = '';
        $table->id = '';

        if($this->num_student == 0) {
            if (!$this->isPdf) {
                echo $OUTPUT->notification(get_string('noenrolleduser', 'local_innoedtools'), 'notifymessage');
            }
        }
        foreach ($this->arr_students as $userid => $fullname) {
            if (!$this->isPdf) {
                $studentnamelink = html_writer::link(new moodle_url('/user/view.php', array('id' => $userid)), $fullname);
                echo $OUTPUT->heading($studentnamelink);
            }

            /*** Prepare table result ***/
            $table->align = $this->generate_column_align();
            $table->size = $this->generate_column_size();
            $table->data = array();

            /*** Prepare sql statement ***/
            $sql = $this->generate_query($userid);
            $rows = $DB->get_records_sql($sql);

            /*** All tags each student ***/
            if ($this->isPdf) {
                $table->data[] = $this->display_line_row();
            }
            foreach ($rows as $row) {
                /*** display data ***/
                $table->data[] = $this->display_row($row);
            }

            /*** Add a totals row. ***/
            if ($this->isPdf) {
                $table->data[] = $this->display_line_row();
            }
            $table->data[] = $this->total_display_row($table->data);

            // Print it.
            if (!$this->isPdf) {
                echo html_writer::table($table);
            } else {
                return $table;
            }
        }
    }

    public function generate_report_pdf() { 
        return $this->generate_report(true);
    }

	public function generate_query($userid) {
        /*** Get using tags for all courses ***/
        $i = 0;
        $sql_inner_tag_in_course = "";
        $sql_inner_course_count = "";
        $sql_inner_course_count_sum = "";
        foreach ($this->arr_courses as $course_id => $course_idnumber) {
            $i++;

            $sql_inner_tag_in_course .= "   SELECT t.rawname, c.id, COUNT(t.rawname) cnt
                                            FROM {tag} t
                                            JOIN {tag_instance} ti ON ti.tagid = t.id
                                            JOIN {post}  p ON p.id = ti.itemid
                                            JOIN {blog_association} ba ON ba.blogid = p.id
                                            JOIN {course} c ON c.id = p.courseid
                                            WHERE (p.publishstate = 'site' OR p.publishstate='public')
                                            AND ti.itemtype = 'post'
                                            AND ti.component = 'core'
                                            AND p.courseid = $course_id
                                            AND p.userid = $userid
                                            GROUP BY t.rawname, c.id
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
                        $sql_inner_course_count_sum
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

        return $col;
	}

	public function generate_column_align() {
		$align = array();

        array_push($align, 'left');
        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($align, 'center'); 
        }
        array_push($align, 'center'); 

        return $align;
	}

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

	public function display_row($row) {
		$data = array();
        $count_this_tag = 0;

		array_push($data, $row->rawname);

        foreach ($this->arr_courses as $course_id => $course_idnumber) {
        	$c = strtolower($course_idnumber);
            array_push($data, $row->$c); 
            $count_this_tag += $row->$c;
        }

        /*** Progress bar ***/
        if (!$this->isPdf) {
            array_push($data, $this->generate_progress_bar($count_this_tag, $this->get_row_possibility()));
        } else {
            array_push($data, $this->convert_to_percent($count_this_tag, $this->get_row_possibility()));
        }

        return $data;
	}

	public function total_display_row($table) {
        global $OUTPUT;

		$sum_data = array();
		$data = array();
        $total_count_this_tag = 0;

		// total count, exclude for first & last columns
		foreach ($table as $key => $value) {
			foreach ($value as $key2 => $value2) {
				if($key2 != 0 && $key2 != count($value)-1) {
					$data[$key2] = $data[$key2] + $value2;
				}
			}
		}

        /*** Aggregate icon ***/
        if (!$this->isPdf) {
            array_push($sum_data, $this->aggregate_icon);
        } else {
            array_push($sum_data, '<dd><b>'.get_string('total', 'local_innoedtools').'</b></dd>');
        }

        foreach ($data as $key => $value) {
            array_push($sum_data, '<b>'.$value.'</b>');
            $total_count_this_tag += $value;
        }

        /*** Progress bar ***/
        if (!$this->isPdf) {
            array_push($sum_data, $this->generate_progress_bar($total_count_this_tag, $this->get_total_possibility()));
        } else {
            array_push($sum_data, $this->convert_to_percent($total_count_this_tag, $this->get_total_possibility()));
        }

		return $sum_data;
	}

    public function get_row_possibility() {
        return $this->num_courses;
    }

    public function get_total_possibility() {
        return $this->num_courses * $this->num_standard_tag;
    }

    public function display_empty_row() {
        $data = array();

        array_push($data, null);

        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($data, null);
        }

        array_push($data, null);

        return $data;
    }

    public function display_line_row() {
        $data = array();

        $line = '<hr />';
        array_push($data, $line);

        for ($i=0; $i < $this->num_courses; $i++) { 
            array_push($data, $line);
        }

        array_push($data, $line);

        return $data;
    }
}