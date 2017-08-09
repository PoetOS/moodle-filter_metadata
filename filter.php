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
        $text = preg_replace_callback(
            '/{([a-zA-Z0-9_]+)}/U',
            function($matches) {
                $result = $this->get_replacement($matches[1]);
                return ($result !== null) ? $result : $matches[0];
            },
            $text
        );
        return $text;
    }

    /**
     * Get the replacement for a given token.
     *
     * @param string $token The token.
     * @return string The replacement.
     */
    public function get_replacement($token) {
        global $PAGE, $USER, $DB;

        // Get the raw database record because some fields in $USER can be out of date (namely lastlogin).
        $user = $DB->get_record('user', ['id' => $USER->id]);

        $token = explode('_', $token);
        array_walk($token, 'trim');
        if (isloggedin()) {
            switch ($token[0]) {
                case 'USER':
                    return $this->get_replacement_user($token, $user);
                case 'COURSE':
                    $course = $PAGE->course;
                    return $this->get_replacement_course($token, $course, $user);
                default:
                    return null;
            }
        } else {
            switch ($token[0]) {
                case 'USER':
                    return $this->get_replacement_user_not_loggedin($token);
                case 'COURSE':
                    $course = $DB->get_record('course', ['id' => 1]);
                    return $this->get_replacement_course_not_loggedin($token, $course);
                default:
                    return null;
            }
        }
    }
    /**
     * Get the replacement for the site information
     *
     * @param array $token The token (split into parts).
     * @param \stdClass $course The applicable course record.
     * @param \stdClass $user The applicable user record.
     * @return string The replacement.
     */
    public function get_replacement_course_not_loggedin(array $token, \stdClass $course) {
        global $DB;
        switch ($token[1]) {
            case 'USER':
                if (!isset($token[2])) {
                    return null;
                }
                return '-';
            case 'FULLNAME':
                return $course->fullname;
            case 'SHORTNAME':
                return $course->shortname;
            case 'IDNUMBER':
                return '-';
            case 'SUMMARY':
                return '-';
            case 'STARTDATE':
                return '-';
            case 'MANAGER':
                return '-';
            case 'COURSECREATOR':
                return '-';
            case 'EDITINGTEACHER':
                return '';
            case 'TEACHER':
                return '-';
            default:
                return null;
        }
    }

    public function get_replacement_course(array $token, \stdClass $course, \stdClass $user) {
        global $DB;
        switch ($token[1]) {
            case 'USER':
                if (!isset($token[2])) {
                    return null;
                }

                switch ($token[2]) {
                    case 'ROLE':
                        $ctx = \context_course::instance($course->id);
                        $sql = 'SELECT ra.*,
                                       r.shortname AS rolename
                                  FROM {role_assignments} ra
                                  JOIN {role} r ON r.id = ra.roleid
                                 WHERE ra.userid = ?
                                       AND ra.contextid = ?';
                        $params = ['userid' => $user->id, 'contextid' => $ctx->id];
                        $rolerecs = $DB->get_records_sql($sql, $params);
                        $rolenames = [];
                        foreach ($rolerecs as $rolerec) {
                            $rolenames[] = $rolerec->rolename;
                        }
                        return (!empty($rolenames)) ? implode(', ', array_unique($rolenames)) : '-';

                    case 'ENROLDATE':
                        // Get enrolment record.
                        $sql = 'SELECT ue.*
                                  FROM {user_enrolments} ue
                                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
                                 WHERE ue.userid = ?';
                        $params = [$course->id, $user->id];
                        $enrolrec = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
                        return (!empty($enrolrec))
                            ? userdate($enrolrec->timecreated)
                            : null;

                    case 'GRADE':
                        $sql = 'SELECT *
                                 FROM {grade_grades} gg
                                 JOIN {grade_items} gi ON gi.id = gg.itemid AND gi.itemtype = "course"
                                WHERE gi.courseid = ? AND gg.userid = ?';
                        $params = [$course->id, $user->id];
                        $graderec = $DB->get_record_sql($sql, $params);
                        return (!empty($graderec))
                            ? $graderec->finalgrade
                            : get_string('nograde', 'filter_personalization');

                    case 'GROUP':
                        $sql = 'SELECT *
                                  FROM {groups} grp
                                  JOIN {groups_members} mbr ON mbr.groupid = grp.id
                                 WHERE grp.courseid = ? AND mbr.userid = ?';
                        $params = [$course->id, $user->id];
                        $grouprecs = $DB->get_records_sql($sql, $params);
                        if (!empty($grouprecs)) {
                            $groupnames = [];
                            foreach ($grouprecs as $grouprec) {
                                $groupnames[] = $grouprec->name;
                            }
                            return implode(', ', array_unique($groupnames));
                        }
                        return null;

                    case 'GROUPING':
                        $sql = 'SELECT *
                                  FROM {groupings} grping
                                  JOIN {groupings_groups} grpinggrp
                                       ON grpinggrp.groupingid = grping.id
                                  JOIN {groups_members} mbr
                                       ON mbr.groupid = grpinggrp.groupid
                                 WHERE grping.courseid = ? AND mbr.userid = ?';
                        $params = [$course->id, $user->id];
                        $grouprecs = $DB->get_records_sql($sql, $params);
                        if (!empty($grouprecs)) {
                            $groupnames = [];
                            foreach ($grouprecs as $grouprec) {
                                $groupnames[] = $grouprec->name;
                            }
                            return implode(', ', array_unique($groupnames));
                        }
                        return null;

                    default:
                        return null;
                }

            case 'FULLNAME':
                return $course->fullname;

            case 'SHORTNAME':
                return $course->shortname;

            case 'IDNUMBER':
                return $course->idnumber;

            case 'SUMMARY':
                return $course->summary;

            case 'STARTDATE':
                return userdate($course->startdate);

            // These get information on users with the specified role in the course.
            case 'MANAGER':
            case 'COURSECREATOR':
            case 'EDITINGTEACHER':
            case 'TEACHER':
                if (!isset($token[2])) {
                    return null;
                }
                // Get users with the specified role.
                $ctx = \context_course::instance($course->id);
                $sql = 'SELECT u.*
                          FROM {role_assignments} ra
                          JOIN {role} r ON r.id = ra.roleid
                          JOIN {user} u ON u.id = ra.userid
                         WHERE r.shortname = ?
                               AND ra.contextid = ?';
                $params = [strtolower($token[1]), $ctx->id];
                $users = $DB->get_records_sql($sql, $params);
                if (empty($users)) {
                    return '-';
                }
                switch ($token[2]) {
                    case 'EMAIL':
                        $emails = [];
                        foreach ($users as $user) {
                            $emails[] = $user->email;
                        }
                        return implode(', ', $emails);

                    case 'FULLNAME':
                        $names = [];
                        foreach ($users as $user) {
                            $names[] = fullname($user);
                        }
                        return implode(', ', $names);

                    default:
                        return null;
                }
                return null;

            default:
                return null;
        }
    }
    /**
     * Get the replacement for the default token.
     *
     * @param array $token The token (split into parts).
     * @param \stdClass $user The applicable user record.
     * @return string The replacement.
     */
    public function get_replacement_user_not_loggedin(array $token) {
        global $OUTPUT, $CFG;
        switch ($token[1]) {
            // User custom fields.
            case 'FIELD':
                return null;
            // Regular user data.
            case 'USERNAME':
                return get_string('guest');
            case 'IDNUMBER':
                return '-';
            case 'EMAIL':
                return '-';
            case 'FIRSTNAME':
                return get_string('guest');
            case 'LASTNAME':
                return get_string('guest');
            case 'FULLNAME':
                return get_string('guest');
            case 'PHONE1':
                return '-';
            case 'PHONE2':
                return '-';
            case 'ADDRESS':
                return '-';
            case 'CITY':
                return '-';
            case 'COUNTRY':
                return '-';
            case 'LASTLOGIN':
                return '-';
            case 'PICTURE':
                return '-';
            default:
                return null;
        }
    }
    /**
     * Get the replacement for a user token.
     *
     * @param array $token The token (split into parts).
     * @param \stdClass $user The applicable user record.
     * @return string The replacement.
     */
    public function get_replacement_user(array $token, \stdClass $user) {
        global $OUTPUT, $CFG;
        switch ($token[1]) {
            // User custom fields.
            case 'FIELD':
                // If there is nothing at index 2, user specified invalid token like {USER_FIELD}.
                if (!isset($token[2]) || $token[2] === '') {
                    return null;
                } else {
                    require_once($CFG->dirroot.'/user/lib.php');
                    $userdetails = user_get_user_details($user, null, ['customfields']);
                    if (!empty($userdetails['customfields'])) {
                        foreach ($userdetails['customfields'] as $fielddata) {
                            if ($fielddata['shortname'] === $token[2]) {
                                return $fielddata['value'];
                            }
                        }
                    }
                }
                return null;

            // Regular user data.
            case 'USERNAME':
                return $user->username;
            case 'IDNUMBER':
                return $user->idnumber;
            case 'EMAIL':
                return $user->email;
            case 'FIRSTNAME':
                return $user->firstname;
            case 'LASTNAME':
                return $user->lastname;
            case 'FULLNAME':
                return fullname($user);
            case 'PHONE1':
                return $user->phone1;
            case 'PHONE2':
                return $user->phone2;
            case 'ADDRESS':
                return $user->address;
            case 'CITY':
                return $user->city;
            case 'COUNTRY':
                return $user->country;
            case 'LASTLOGIN':
                return userdate($user->lastlogin);
            case 'PICTURE':
                return $OUTPUT->user_picture($user, ['size' => 100, 'class' => 'userpicture']);
            default:
                return null;
        }
    }
}

