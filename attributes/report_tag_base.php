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

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

/**
 * Base class of report
 *
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tag_base {

    /**
     * @var int Number of course.
     * @protected
     */
    protected $numcourses = 0;

    /**
     * @var int Number of standard tag.
     * @protected
     */
    protected $numstandardtag = 0;

    /**
     * @var int Number of student.
     * @protected
     */
    protected $numstudent = 0;

    /**
     * @var array Array of course.
     * @protected
     */
    protected $arrcourses = array();

    /**
     * @var array Array of course parameter.
     * @protected
     */
    protected $arrcoursesparam = array();

    /**
     * @var array Array of course context.
     * @protected
     */
    protected $arrcoursescontext = array();

    /**
     * @var array Array of student.
     * @protected
     */
    protected $arrstudents = array();

    /**
     * @var array Array of blog.
     * @protected
     */
    protected $arrblogs = array();

    /**
     * @var int Get a course context.
     * @protected
     */
    protected $contextcourseid = 0;

    /**
     * @var string Aggregate icon in html.
     * @protected
     */
    protected $aggregateicon = null;

    /**
     * Constructor
     *
     * @param boolean $isviewall
     */
    public function __construct($isviewall = true) {
        $this->initial_course();
        $this->initial_standard_tag();
        $this->initial_context_course_id();
        $this->initial_student($isviewall); // Must be after context course id.
        $this->initial_aggregate_icon();
    }

    /**
     * Initial course data
     */
    protected function initial_course() {
        global $DB;

        $sqlcourselist = "SELECT id,sortorder, fullname, shortname, idnumber FROM {course} WHERE idnumber <> '' AND visible = 1 ORDER BY sortorder";
        $rowscourses = $DB->get_records_sql($sqlcourselist);
        $this->numcourses = count($rowscourses);

        foreach ($rowscourses as $rowscourse) {
            $this->arrcourses[$rowscourse->id] = $rowscourse->idnumber;
            $this->arrcoursescontext[$rowscourse->id] = context_course::instance($rowscourse->id)->id;
        }
    }

    /**
     * Initial standard tag data
     */
    protected function initial_standard_tag() {
        global $DB;

        $sqlstandardtags = "SELECT COUNT(id) FROM {tag} WHERE isstandard = 1";
        $this->numstandardtag = $DB->count_records_sql($sqlstandardtags);
    }

    /**
     * Initial context course id data
     */
    protected function initial_context_course_id() {
        foreach ($this->arrcoursescontext as $key => $value) {
            // Assign just 1 of course context.
            $this->contextcourseid = $value;
            break;
        }
    }

    /**
     * Initial student data
     *
     * @param boolean $isviewall
     */
    protected function initial_student($isviewall = true) {
        global $DB;

        $sqlstudentslist = $this->generate_query_enrolled_student($isviewall);
        $rowsstudents = $DB->get_records_sql($sqlstudentslist);
        $this->numstudent = count($rowsstudents);

        foreach ($rowsstudents as $rowsstudent) {
            $this->arrstudents[$rowsstudent->userid] = $rowsstudent->fullname;
        }
    }

    /**
     * Get all enrolled student
     *
     * @param boolean $isviewall
     */
    protected function generate_query_enrolled_student($isviewall = true) {
        global $DB, $USER, $OUTPUT;
        // Criteria query for filter user.
        $filtercurrentuser = null;

        $contextcourseid = $this->contextcourseid;

        if (!$isviewall) {
            $uid = optional_param('uid', null, PARAM_INT);
            $currentuser = isset($uid) ? $uid : $USER->id;
            $filtercurrentuser = ' AND u.id = ' . $currentuser;
        }

        $sqlstudentslist = "SELECT u.id userid, CONCAT(u.firstname, ' ', u.lastname) fullname FROM {role_assignments} ra INNER JOIN {role} r ON r.id = ra.roleid INNER JOIN {user} u ON u.id = ra.userid WHERE ra.contextid = $contextcourseid AND archetype = 'student' AND u.deleted = 0 $filtercurrentuser ORDER BY u.firstname, u.lastname, u.email";

        return $sqlstudentslist;
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
        $this->aggregateicon = '<dd>'.
                                   $OUTPUT->pix_icon($icon->pix, $icon->title, $icon->component, $icon->attributes).
                                   '<b>'.get_string('total').'</b>'.
                               '</dd>';
    }

    /**
     * Get number of course
     */
    public function get_num_courses() {
        return $this->numcourses;
    }

    /**
     * Get number of standard tag
     */
    public function get_num_standard_tag() {
        return $this->numstandardtag;
    }

    /**
     * Get number of student
     */
    public function get_num_student() {
        return $this->numstudent;
    }

    /**
     * Get array of student
     */
    public function get_array_students() {
        return $this->arrstudents;
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

    /**
     * Compare string with case sensitive depends on database type
     *
     * @param string $text
     */
    protected function get_strtolower_from_dbtype($text) {
        global $CFG;

        if ($CFG->dbtype == 'pgsql') {
            return $text;
        } else {
            return strtolower($text);
        }
    }
}