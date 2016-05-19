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
 * Condition main class.
 *
 * @package availability_badge
 * @copyright 2016 Blackboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int ID of badge that this condition requires */
    protected $badgeid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get badge id.
        if (is_int($structure->id)) {
            $this->badgeid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for badge condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'badge');
        if ($this->badgeid) {
            $result->id = $this->badgeid;
        }
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $CFG;
        require_once($CFG->libdir . '/badgeslib.php');

        $allow = true;
        $course = $info->get_course();
        $context = \context_course::instance($course->id);
        if (!has_capability('moodle/badges:manageglobalsettings', $context, $userid)) {
            // Get all badges the user belongs to.
            $badges = badges_get_user_badges($userid);
            if ($this->badgeid) {
                $allow = false;
                foreach ($badges as $key => $badge) {
                    if ($badge->id == $this->badgeid) {
                        $allow = true;
                    }
                }
            }

            // The NOT condition.
            if ($not) {
                $allow = !$allow;
            }
        }
        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->badgeid) {
            $course = $info->get_course();
            $context = \context_course::instance($course->id);
            $courseid = 'all';
            $helper = new \availability_badge\helper;
            $badges = $helper::get_all_badges();
            $badgenames = $helper::get_badge_names();
            if (empty($badgenames[$courseid])) {
                $badgenames[$courseid] = $helper::get_all_badges($courseid);
            }

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->badgeid, $badgenames[$courseid])) {
                $name = get_string('missing', 'availability_badge');
            } else {
                $name = format_string($badgenames[$courseid][$this->badgeid]->name, true,
                        array('context' => $context));
            }
        }

        return get_string($not ? 'requires_notbadge' : 'requires_badge',
                'availability_badge', $name);
    }

    protected function get_debug_string() {
        return $this->badgeid ? '#' . $this->badgeid : '';
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        if (!$this->badgeid) {
            return false;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'badge', $this->badgeid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('badge',
                    array('id' => $this->badgeid, 'courseid' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->badgeid = -1;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on badge that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->badgeid = (int)$rec->newitemid;
        }
        return true;
    }

    public function update_dependency_id($table, $oldid, $newid) {
        if ($table === 'badge' && (int)$this->badgeid === (int)$oldid) {
            $this->badgeid = $newid;
            return true;
        } else {
            return false;
        }
    }

}
