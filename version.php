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
 * Metadata filter information.
 *
 * @package filter_metadata
 * @author  Mike Churchward
 * @copyright 2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2017080900;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2016052300; // Moodle 3.1 release and upwards.
$plugin->component = 'filter_metadata';
$plugin->release  = 'BETA3.1.0 (Build - 2017080900)';
$plugin->maturity  = MATURITY_BETA;
$plugin->dependencies = [
    'local_metadata' => 2017070102,
];