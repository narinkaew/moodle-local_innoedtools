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
 * Display list of user.
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/local/innoedtools/attributes/report_tag_base.php');

global $DB, $PAGE, $USER, $SITE;

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$sitecontext = context_system::instance();
$PAGE->set_url('/local/innoedtools/innovationpdf/user.php');
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('standard');

$keylabel = get_string('navigation_label_innovationpdf', 'local_innoedtools');
$mainnode = $PAGE->navigation->find($keylabel, null);
$mainnode->make_active();

$PAGE->set_title(get_string('titletext', 'local_innoedtools'));
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('headingtext', 'local_innoedtools'));

// Get list of students.
$reporttagbase = new report_tag_base();
$arrstudents = $reporttagbase->get_array_students();

// Initialise the table.
$table = new html_table();
$table->head = array('User ID', 'First name / Surname', 'Email address', 'Telephone', 'View');
$table->align = array('left', 'left', 'left', 'left', 'left');
$table->size = array('10%', '35%', '30%', '15%', '10%');
$table->data = array();

foreach ($arrstudents as $id => $value) {
    $userobj = $DB->get_record('user', array('id' => $id));

    $row = array();
    $row[] = empty($userobj->idnumber) ? '-' : $userobj->idnumber;
    $row[] = fullname($userobj);
    $row[] = empty($userobj->email) ? '-' : $userobj->email;
    $row[] = empty($userobj->phone2) ? (empty($userobj->phone1) ? '-' : $userobj->phone1) : $userobj->phone2;
    $row[] = html_writer::link(
                                new moodle_url(
                                    'index.php',
                                    array('uid' => $userobj->id)
                                ),
                                html_writer::empty_tag(
                                    'img',
                                    array('src' => $OUTPUT->pix_url('t/viewdetails'), 'alt' => 'View', 'class' => 'iconsmall')
                                ),
                                array('title' => 'View', 'target' => '_blank')
                            );

    $table->data[] = $row;
}

echo html_writer::table($table);
echo $OUTPUT->footer();