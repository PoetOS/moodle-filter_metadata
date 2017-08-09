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
 * Filter class for filter_metadata.
 *
 * @package filter_metadata
 * @author  Mike Churchward
 * @copyright 2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Filter class
 */
class filter_metadata extends \moodle_text_filter {

    /**
     * Apply the filter to the text
     *
     * @param string $text String to be processed.
     * @param array $options Filter options.
     * @return string Text after processing.
     */
    public function filter($text, array $options = []) {
        $filtered = $text; // We need to return the original value if regex fails!
        // Patterns look like "{{metadata::course=234::credits}}", where '=234' is optional.
        $filtered = preg_replace_callback('/{{metadata::([a-zA-Z_]+(?:=[0-9])*)::([a-zA-Z0-9_]+)}}/U',
            'self::find_metadata_callback', $filtered);
        if (empty($filtered)) {
            // If $filtered is emtpy return original $text.
            return $text;
        } else {
            return $filtered;
        }
    }

    /**
     * Callback function to be used by the main filter
     *
     * @param $match array An array of matched groups, where [1] is the URL matched.
     *
     */
    private static function find_metadata_callback($match) {
        $result = $match[1] . ' = ' . $match[2];
        return $result;
    }
}