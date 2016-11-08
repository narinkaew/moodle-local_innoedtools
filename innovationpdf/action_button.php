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
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$actionchoices = array();
$actionchoices['1'] = get_string('preview', 'local_innoedtools');
$actionchoices['2'] = get_string('print', 'local_innoedtools');

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo '<form method="get" action="." id="exportpdfform">';
echo '	<div>';
echo 		$OUTPUT->heading(get_string('actiontext', 'local_innoedtools'));
echo 		html_writer::select($actionchoices, 'act', $act);
echo '		<input type="submit" id="exportpdfsubmit" value="' . get_string('submittext', 'local_innoedtools') . '" />';
echo '		<p></p>';
echo '	</div>';
echo '	<input type="hidden" name="uid" value="'.$this->current_user.'"/>';
echo '</form>';
echo $OUTPUT->box_end();