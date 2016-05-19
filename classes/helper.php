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
 * Condition main class.
 *
 * @package availability_badge
 * @copyright 2016 Blackboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_badge;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition helper class.
 *
 * @package availability_badge
 * @copyright 2016 Blackboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** @var array Array from badge id => name */
    protected static $badgenames = array();


    /**
     * Wipes the static cache used to store badge names.
     */
    public static function wipe_static_cache() {
        self::$badgenames = array();
    }

    /**
     * Gets badge names
     *
     * @return array Array of all the badge names
     */
    public static function get_badge_names() {
        return self::$badgenames;
    }
    /**
     * Gets all badges for the given course.
     *
     * @param mixed $courseid Course id or all badges
     * @return array Array of all the badge objects
     */
    public static function get_all_badges($courseid = 'all') {
        global $CFG, $DB;
        require_once($CFG->libdir . '/badgeslib.php');

        if (empty(self::$badgenames[$courseid])) {
            if ($courseid == 'all') {
                list($bsql, $params) = $DB->get_in_or_equal(BADGE_STATUS_INACTIVE, SQL_PARAMS_NAMED, 'status', false);
                $sql = "SELECT * FROM {badge} WHERE status $bsql";
                self::$badgenames[$courseid] = $DB->get_records_sql($sql, $params);
            } else if ($courseid == 0) {
                self::$badgenames[$courseid] = badges_get_badges(BADGE_TYPE_SITE, $courseid);
            } else {
                self::$badgenames[$courseid] = badges_get_badges(BADGE_TYPE_COURSE, $courseid);
            }
        }

        return self::$badgenames[$courseid];
    }
}
