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
 * Access file definitions for plugin local_attributes
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'local/innoedtools:canviewtagreport' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user'           => CAP_ALLOW,
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'local/innoedtools:viewalltagreport' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW
        )
    ),

    'local/innoedtools:myviewtagreport' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user'      => CAP_ALLOW,
            'student'   => CAP_ALLOW,
        )
    ),

    'local/innoedtools:canviewinnovationpdf' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/innoedtools:viewallinnovationpdf' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW
        )
    ),

);
