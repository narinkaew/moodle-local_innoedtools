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
 * child class of TCPDF
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('K_TCPDF_CALLS_IN_HTML', true);

require_once($CFG->dirroot.'/lib/pdflib.php');

/**
 * Extend TCPDF to override header function.
 * 
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mypdf extends TCPDF {

    /**
     * Override the full background image on header.
     */
    public function Header() {
        global $CFG;

        // Get the current page break margin.
        $bMargin = $this->getBreakMargin();

        // Get current auto-page-break mode.
        $auto_page_break = $this->AutoPageBreak;

        // Disable auto-page-break.
        $this->SetAutoPageBreak(false, 0);

        // Set background image.
        $img_file = $CFG->dirroot.'/local/innoedtools/innovationpdf/image/'.get_string('backgroundimagefile', 'local_innoedtools');
        $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

        // Restore auto-page-break status.
        $this->SetAutoPageBreak($auto_page_break, $bMargin);

        // Set the starting point for the page content.
        $this->setPageMark();
    }
}