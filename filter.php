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

defined('MOODLE_INTERNAL') || die();

/**
 * Filter class
 */
class filter_metadata extends \moodle_text_filter {

    /**
     * @var array $cache Cache of found metadata values.
     */
    protected $cache = [];

    /**
     * Apply the filter to the text
     *
     * @param string $text String to be processed.
     * @param array $options Filter options.
     * @return string Text after processing.
     */
    public function filter($text, array $options = []) {
        // Patterns look like "{{metadata::course=234::credits}}", where '=234' is optional.
        $filtered = preg_replace_callback('/{{metadata::([a-zA-Z_]+(?:=[0-9])*)::([a-zA-Z0-9_]+)}}/U',
            'self::find_metadata_callback', $text);
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
    private function find_metadata_callback($match) {
        if (count($match) <= 1) {
            return '';
        }

        // Store the contextlevel, fieldname and instanceid for processing later.
        list($contextname, $contextinstance) = array_merge(explode('=', $match[1]), [false]);
        if ($contextinstance === false) {
            // Need to determine what the context instance is.
            if (!($contextinstance = \local_metadata\context\context_handler::find_instanceid($contextname))) {
                return get_string('errornocontextvalue', 'filter_metadata');
            }
        }

        // TODO - Need to use the fieldtype to get the data display.
        // TODO - Need to check if user has access to the data field.
        if (!isset($this->cache[$contextname][$match[2]][$contextinstance]) ||
            empty($this->cache[$contextname][$match[2]][$contextinstance])) {
            $this->cache[$contextname][$match[2]][$contextinstance] = $this->get_data($contextname, $match[2], $contextinstance);
        }

        return $this->cache[$contextname][$match[2]][$contextinstance];
    }

    /**
     * Return the data value for the specific metadata instance.
     *
     * @param string $contextname The name of the context.
     * @param string $fieldname The shortname of the metedata field type.
     * @param int $instanceid The value of the context instance the data is attached to.
     * @return string The value of the metadata field instance.
     */
    private function get_data($contextname, $fieldname, $instanceid) {
        global $DB;

        try {
            $contexthandler = \local_metadata\context\context_handler::factory($contextname, $instanceid);
            $params = [$instanceid, $contexthandler->contextlevel, $fieldname];
            $sql = 'SELECT m.data FROM {local_metadata_field} mf ' .
                'INNER JOIN {local_metadata} m ON mf.id = m.fieldid AND m.instanceid = ? ' .
                'WHERE mf.contextlevel = ? AND mf.shortname = ? ';
            $data = $DB->get_field_sql($sql, $params);
            $data = empty($data) ? get_string('errornocontextvalue', 'filter_metadata') : $data;
        } catch (\moodle_exception $e) {
            debugging('Exception detected when using metadata filter: ' . $e->getMessage(), DEBUG_NORMAL, $e->getTrace());
            $data = get_string('errornocontextvalue', 'filter_metadata');
        }
        return $data;
    }
}