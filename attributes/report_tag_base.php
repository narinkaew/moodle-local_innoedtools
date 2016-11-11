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
 * Base class of report
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

/**
 * Base class of report
 *
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tag_base {

    /** Number of course. */
    protected $num_courses = 0;
    /** Number of standard tag. */
    protected $num_standard_tag = 0;
    /** Number of student. */
	protected $num_student = 0;

    /** Array of course. */
    protected $arr_courses = array();
    /** Array of course context. */
    protected $arr_courses_context = array();
    /** Array of student. */
    protected $arr_students = array();
    /** Array of blog. */
    protected $arr_blogs = array();

    /** Get a course context. */
    protected $context_course_id = 0;
    /** Aggregate icon in html. */
    protected $aggregate_icon = null;

    /**
     * Constructor
     *
     * @param boolean $isViewAll
     */
	public function __construct($isViewAll = true) {
        $this->initial_course();
        $this->initial_standard_tag();
        $this->initial_context_course_id();
        $this->initial_student($isViewAll); // Must be after context course id.
        $this->initial_aggregate_icon();
	}

    /**
     * Initial course data
     */
    protected function initial_course() {
        global $DB;

        $sql_course_list = "    SELECT id, sortorder, fullname, shortname, idnumber
                                FROM {course} 
                                WHERE idnumber <> ''
                                AND visible = 1
                                ORDER BY sortorder
                            ";
        $rows_courses = $DB->get_records_sql($sql_course_list);
        $this->num_courses = count($rows_courses);

        foreach ($rows_courses as $rows_course) {
            $this->arr_courses[$rows_course->id] = $rows_course->idnumber;
            $this->arr_courses_context[$rows_course->id] = context_course::instance($rows_course->id)->id;
        }
    }

    /**
     * Initial standard tag data
     */
    protected function initial_standard_tag() {
        global $DB;

        $sql_standard_tags = "  SELECT COUNT(id) 
                                FROM {tag} 
                                WHERE isstandard = 1
                            ";
        $this->num_standard_tag = $DB->count_records_sql($sql_standard_tags);
    }

    /**
     * Initial context course id data
     */
    protected function initial_context_course_id() {
        foreach ($this->arr_courses_context as $key => $value) {
            // Assign just 1 of course context.
            $this->context_course_id = $value;
            break;
        }
    }

    /**
     * Initial student data
     *
     * @param boolean $isViewAll
     */
    protected function initial_student($isViewAll = true) {
        global $DB;

        $sql_students_list = $this->generate_query_enrolled_student($isViewAll);
        $rows_students = $DB->get_records_sql($sql_students_list);
        $this->num_student = count($rows_students);

        foreach ($rows_students as $rows_student) {
            $this->arr_students[$rows_student->userid] = $rows_student->fullname;
        }
    }

    /**
     * Get all enrolled student
     *
     * @param boolean $isViewAll
     */
    protected function generate_query_enrolled_student($isViewAll = true) {
        global $DB, $USER, $OUTPUT;

        $context_course_id = $this->context_course_id;

        if (!$isViewAll) {
            $uid = optional_param('uid', null, PARAM_INT);
            $current_user = isset($uid) ? $uid : $USER->id;
            $filter_current_user = ' AND u.id = ' . $current_user;
        }

        $sql_students_list = "  SELECT u.id userid, CONCAT(u.firstname, ' ', u.lastname) fullname
                                FROM {role_assignments} ra
                                INNER JOIN {role} r ON r.id = ra.roleid
                                INNER JOIN {user} u ON u.id = ra.userid
                                WHERE ra.contextid = $context_course_id
                                AND archetype = 'student'
                                AND u.deleted = 0
                                $filter_current_user
                                ORDER BY u.firstname, u.lastname, u.email
                            ";

        return $sql_students_list;
    }

    /**
     * Declare aggregate icon
     */
    protected function initial_aggregate_icon() {
        global $OUTPUT;

        // Aggregate icon.
        $icon = new stdClass();
        $icon->attributes = array(
            'class' => 'icon itemicon'
        );
        $icon->component = 'moodle';
        $icon->title = 'Sum';
        $icon->pix = 'i/agg_sum';
        $this->aggregate_icon = '<dd>'.$OUTPUT->pix_icon($icon->pix, $icon->title, $icon->component, $icon->attributes).'<b>'.get_string('total').'</b></dd>';
    }

    /**
     * Get number of course
     */
    public function get_num_courses() {
        return $this->num_courses;
    }

    /**
     * Get number of standard tag
     */
    public function get_num_standard_tag() {
        return $this->num_standard_tag;
    }

    /**
     * Get number of student
     */
    public function get_num_student() {
        return $this->num_student;
    }

    /**
     * Get array of student
     */
    public function get_array_students() {
        return $this->arr_students;
    }

    /**
     * Render progress bar
     *
     * @param int $x Numerator
     * @param int $y Denominator 
     */
	protected function generate_progress_bar($x, $y) {
		$percent = $this->convert_to_percent($x, $y);

        $html = '';
        $html .= html_writer::start_tag('div', array('class' => 'block_xp-level-progress'));
        $html .= html_writer::tag('div', '', array('style' => 'width: ' . $percent, 'class' => 'bar'));
        $html .= html_writer::tag('div', $percent, array('class' => 'txt'));
        $html .= html_writer::end_tag('div');

        return $html;
	}

    /**
     * Convert number to percent
     *
     * @param int $x Numerator
     * @param int $y Denominator 
     */
    protected function convert_to_percent($x, $y) {
        return round(($x / $y) * 100, 2) . '%';
    }
}