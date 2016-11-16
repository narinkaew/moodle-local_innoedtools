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
 * Index file.
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/locallib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/comment/lib.php');
require_once('portfolio_listing.php');

global $DB, $PAGE, $USER, $SITE;

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

// Get URL parameters.
$uid = optional_param('uid', null, PARAM_INT);
$act = optional_param('act', '', PARAM_SAFEDIR);

// Admin/Teacher will redirect to user list page.
$systemcontext = context_system::instance();
if (is_siteadmin() || has_capability('local/innoedtools:viewallinnovationpdf', $systemcontext)) {
    // No select user, redirect to user list page.
    if (!isset($uid)) {
        $returnurl = new moodle_url('/local/innoedtools/innovationpdf/user.php');
        redirect($returnurl);
    } else {
        // Choose preview.
        if ($act == '1') {
            $returnurl = new moodle_url('/local/innoedtools/innovationpdf/export.php?preview=1&uid='.$uid);
            redirect($returnurl);
        } else if ($act == '2') {
            // Choose print.
            $returnurl = new moodle_url('/local/innoedtools/innovationpdf/export.php?uid='.$uid);
            redirect($returnurl);
        }
    }
} else {
    // Student see only their own.
    // Choose preview.
    if ($act == '1') {
        $returnurl = new moodle_url('/local/innoedtools/innovationpdf/export.php?preview=1');
        redirect($returnurl);
    } else if ($act == '2') {
        // Choose print.
        $returnurl = new moodle_url('/local/innoedtools/innovationpdf/export.php');
        redirect($returnurl);
    }
}

comment::init();

$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('standard');

$PAGE->set_title(get_string('titletext', 'local_innoedtools'));
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('headingtext', 'local_innoedtools'));

// Display innovation blogs.
$portfoliolisting = new portfolio_listing();
$portfoliolisting->display_my_entries();

echo $OUTPUT->footer();