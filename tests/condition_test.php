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
 * Unit tests for the condition.
 *
 * @package availability_badge
 * @copyright 2016 Blackboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_badge\condition;

/**
 * Unit tests for the condition.
 *
 * @package availability_badge
 * @copyright 2016 Blackboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_badge_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using condition.
     */
    public function test_usage() {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/badgeslib.php');
        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        // Erase static cache before test.
        $helper = new \availability_badge\helper;
        $helper::wipe_static_cache();

        // Create a course with activity and auto completion tracking.
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $info = new \core_availability\mock_info($this->course, $this->user->id);

        $fordb = new stdClass();
        $fordb->id = null;
        $fordb->name = "Test badge";
        $fordb->description = "Testing badges";
        $fordb->timecreated = time();
        $fordb->timemodified = time();
        $fordb->usercreated = $this->user->id;
        $fordb->usermodified = $this->user->id;
        $fordb->issuername = "Test issuer";
        $fordb->issuerurl = "http://issuer-url.domain.co.nz";
        $fordb->issuercontact = "issuer@example.com";
        $fordb->expiredate = null;
        $fordb->expireperiod = null;
        $fordb->type = BADGE_TYPE_SITE;
        $fordb->courseid = null;
        $fordb->messagesubject = "Test message subject";
        $fordb->message = "Test message body";
        $fordb->attachment = 1;
        $fordb->notification = 0;
        $fordb->status = BADGE_STATUS_ACTIVE;

        $this->badgeid = $DB->insert_record('badge', $fordb);

        // Get manual enrolment plugin and enrol user.
        require_once($CFG->dirroot.'/enrol/manual/locallib.php');
        $manplugin = enrol_get_plugin('manual');
        $maninstance = $DB->get_record('enrol', array('courseid' => $this->course->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manplugin->enrol_user($maninstance, $this->user->id, $studentrole->id);
        $this->assertEquals(1, $DB->count_records('user_enrolments'));

        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $this->module = $this->getDataGenerator()->create_module('forum', array('course' => $this->course->id), $completionauto);


        $cond = new condition((object)array('id' => (int)$this->badgeid));

        // Check if available (when not available).
        $this->assertFalse($cond->is_available(false, $info, true, $this->user->id));

        $badge = new badge($this->badgeid);
        $badge->issue($this->user->id, true);
        $this->assertTrue($badge->is_issued($this->user->id));

        // Erase static cache before test.
        $helper::wipe_static_cache();

        $cond = new condition((object)array('id' => (int)$this->badgeid));

        // Check if available (when available).
        $this->assertTrue($cond->is_available(false, $info, true, $this->user->id));
    }
}
