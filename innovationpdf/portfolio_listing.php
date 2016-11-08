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
 * portfolio blog listing
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/locallib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/comment/lib.php');

class portfolio_listing extends blog_listing {

    protected $current_user = 0;
    protected $num_blogs = 0;

    public function __construct() {
        global $USER;

        $uid = optional_param('uid', null, PARAM_INT);
        $this->current_user = isset($uid) ? $uid : $USER->id;
    }

    public function display_my_entries() {
    	global $CFG, $DB, $PAGE, $USER, $OUTPUT;

        $systemcontext = context_system::instance();
        if (!(is_siteadmin() || has_capability('local/innoedtools:viewallinnovationpdf', $systemcontext))) {
            if ($this->current_user != $USER->id) {
                echo $OUTPUT->notification(get_string('nopermission', 'local_innoedtools'), 'notifymessage');
                return;
            }
        }

        // Blog renderer.
        $output = $PAGE->get_renderer('blog');

        $entries = $this->get_innovation_blog();
        if ($this->num_blogs != 0) {
            require_once($CFG->dirroot .'/local/innoedtools/innovationpdf/action_button.php');
            
            foreach ($entries as $entry) {

                $blogentry = new blog_entry(null, $entry);

                // // Get the required blog entry data to render it.
                $blogentry->prepare_render();
                echo $output->render($blogentry);
            }
            return;
        }
        else {
            echo $OUTPUT->notification(get_string('noentriesyet', 'local_innoedtools'), 'notifymessage');
            return;
        }
    }

    /**
    *  Copy from default blog page query
    *    $sqlarray = $this->get_entry_fetch_sql(false, 'userid ASC, courseid ASC');
    *    $entries = $DB->get_records_sql($sqlarray['sql'], $sqlarray['params'], $start, $limit);
    *    $totalentries = $this->count_entries();
    *
    **/
    protected function get_innovation_blog() {
        global $DB;

        $sql_blog_list = "  SELECT p.*, u.id AS useridalias,u.picture,u.firstname,u.lastname,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.imagealt,u.email 
                            FROM {post} p
                            INNER JOIN {user} u ON p.userid = u.id
                            INNER JOIN {course} c ON p.courseid = c.id
                            WHERE u.deleted = 0 
                            AND (p.module = 'blog' OR p.module = 'blog_external') 
                            AND p.courseid <> 0
                            AND c.idnumber <> ''
                            AND c.visible = 1
                            AND p.userid = $this->current_user
                            ORDER BY userid ASC, courseid ASC
                        ";
        $rows_blogs = $DB->get_records_sql($sql_blog_list);

        $this->num_blogs = count($rows_blogs);

        return $rows_blogs;
    }
}