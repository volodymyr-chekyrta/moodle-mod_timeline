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
 * Lists all Interactive Timeline instances in a course.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/timeline/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/timeline/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'mod_timeline'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_timeline'));

if (!$timelines = get_all_instances_in_course('timeline', $course)) {
    echo $OUTPUT->notification(get_string('noinstances', 'moodle'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('sectionname', 'format_'.$course->format),
    get_string('intro', 'mod_timeline'),
];
$table->data = [];

foreach ($timelines as $timeline) {
    $cm = get_coursemodule_from_instance('timeline', $timeline->id, $course->id, false, MUST_EXIST);
    $link = html_writer::link(
        new moodle_url('/mod/timeline/view.php', ['id' => $cm->id]),
        format_string($timeline->name, true, ['context' => $context])
    );

    $intro = format_module_intro('timeline', $timeline, $cm->id);

    $table->data[] = [
        $link,
        $timeline->section,
        $intro,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
