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
 * Index file
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../config.php');
require_once('report_tag_base.php');
require_once('report_tag_by_overall.php');
require_once('report_tag_by_tags.php');
require_once('report_tag_by_students.php');

global $DB, $PAGE, $USER;

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$systemcontext = context_system::instance();
$PAGE->set_url('/local/innoedtools/attributes/index.php');
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('report');

// Get url parameters.
$requestedqtype = optional_param('qtype', '', PARAM_SAFEDIR);

// Include css.
$PAGE->requires->css('/local/innoedtools/attributes/styles.css');

// Page title.
$PAGE->set_title(get_string('previewtitle', 'local_innoedtools'));
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('managepageheading', 'local_innoedtools'));

// Body.
$reporttagbase = new report_tag_base();
if ($reporttagbase->get_num_standard_tag() == 0) {
    echo $OUTPUT->notification(get_string('nostandardtag', 'local_innoedtools'), 'notifymessage');
} else if ($reporttagbase->get_num_courses() == 0) {
    echo $OUTPUT->notification(get_string('nocourses', 'local_innoedtools'), 'notifymessage');
} else {
    // Teachers will see all student reports.
    if (is_siteadmin() || has_capability('local/innoedtools:viewalltagreport', $systemcontext)) {

        $qtypechoices = array();
        $qtypechoices['1'] = get_string('typeall', 'local_innoedtools');
        $qtypechoices['2'] = get_string('typebytags', 'local_innoedtools');
        $qtypechoices['3'] = get_string('typebystudents', 'local_innoedtools');

        // Print the attributes form.
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
        echo '<form method="get" action="." id="attributesform">';
        echo '  <div>';
        echo        $OUTPUT->heading(get_string('reportsettings', 'local_innoedtools'));
        echo        html_writer::select($qtypechoices, 'qtype', $requestedqtype);
        echo '      <input type="submit" id="attributessubmit" value="' . get_string('getreport', 'local_innoedtools') . '" />';
        echo '      <p></p>';
        echo '  </div>';
        echo '</form>';
        echo $OUTPUT->box_end();

        // If we have a value to report on, generate the report.
        if ($requestedqtype) {
            // Print the report heading.
            $title = get_string('reportforattributetype', 'local_innoedtools', $qtypechoices[$requestedqtype]);
            echo $OUTPUT->heading($title);

            if ($requestedqtype == '2') {
                // By Tags.
                $reporttagbytags = new report_tag_by_tags();
                $reporttagbytags->generate_report();
            } else if ($requestedqtype == '3') {
                // By Students.
                $reporttagbystudents = new report_tag_by_students();
                $reporttagbystudents->generate_report();
            } else {
                // Overall.
                $reporttagbyoverall = new report_tag_by_overall();
                $reporttagbyoverall->generate_report();
            }
        }
    } else {
        // Students will see just their own report.
        $reporttagbyoverall = new report_tag_by_overall(false);
        $reporttagbyoverall->generate_report();
    }
}

// Footer.
echo $OUTPUT->footer();