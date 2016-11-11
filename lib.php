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
 * Attributes library.
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the navigation block and adds a new node 'Inno Ed Tools'. This is visible if the current user has the capability.
 * @param global_navigation $nav
 */
function local_innoedtools_extend_navigation(global_navigation $navigation) {
    global $PAGE, $DB;

    if (has_capability('local/innoedtools:canviewtagreport', $PAGE->context) || has_capability('local/innoedtools:canviewinnovationpdf', $PAGE->context)) {
	    // get the "Site pages" node
	    $nodesitepages = $navigation->children->get('1');
	    $navigation_label_plugin = get_string('navigation_label_plugin', 'local_innoedtools');
	    $node_plugin = $nodesitepages->add($navigation_label_plugin, null, null, null, $navigation_label_plugin);

	    if (has_capability('local/innoedtools:canviewtagreport', $PAGE->context)) {
		    
		  	$node_plugin = $navigation->find($navigation_label_plugin);

		  	// Create a child node
		  	$navigation_label_attributes = get_string('navigation_label_attributes', 'local_innoedtools');
		  	$url_attributes = new moodle_url('/local/innoedtools/attributes/index.php');
		    $node_attributes = $node_plugin->add($navigation_label_attributes, $url_attributes, null, null, $navigation_label_attributes);
		}

	    if (has_capability('local/innoedtools:canviewinnovationpdf', $PAGE->context)) {
		
		  	$node_plugin = $navigation->find($navigation_label_plugin);

		  	// Create a child node
		  	$navigation_label_innovationpdf = get_string('navigation_label_innovationpdf', 'local_innoedtools');
		  	$url_innovationpdf = new moodle_url('/local/innoedtools/innovationpdf/index.php');
		    $node_innovationpdf = $node_plugin->add($navigation_label_innovationpdf, $url_innovationpdf, null, null, $navigation_label_innovationpdf);
		}
	}
}