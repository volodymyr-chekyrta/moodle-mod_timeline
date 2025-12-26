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
 * View page for the Interactive Timeline activity.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/timeline/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$n  = optional_param('n', 0, PARAM_INT);  // Timeline instance ID.

if ($id) {
    $cm = get_coursemodule_from_id('timeline', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $timeline = $DB->get_record('timeline', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $timeline = $DB->get_record('timeline', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $timeline->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('timeline', $timeline->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingparameter');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/timeline/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($timeline->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->activityheader->disable();

$events = timeline_decode_events($timeline->eventsjson);
if (!empty($events)) {
    usort($events, static function($a, $b) {
        return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
    });
}

$clientevents = [];
foreach ($events as $index => $event) {
    $title = $event['title'] ?? '';
    $timestamp = $event['timestamp'] ?? 0;
    $shortdesc = $event['shortdesc'] ?? '';
    $description = $event['description'] ?? '';
    $originalindex = $event['originalindex'] ?? $index;

    // Rewrite pluginfile URLs manually first
    $description = file_rewrite_pluginfile_urls(
        $description,
        'pluginfile.php',
        $context->id,
        'mod_timeline',
        'eventdescription',
        $originalindex
    );
    
    $contenttype = $event['contenttype'] ?? 'default';
    
    $clientevents[] = [
        'index' => $index,
        'uid' => $event['uid'] ?? '',
        'title' => format_string($title, true, ['context' => $context]),
        'shortdesc' => format_string($shortdesc, true, ['context' => $context]),
        'hasshortdesc' => trim($shortdesc) !== '',
        'dateshort' => !empty($timestamp) ? userdate($timestamp, get_string('strftimedate', 'langconfig')) : '',
        'datestring' => !empty($timestamp) ? userdate($timestamp, get_string('strftimedaydate', 'langconfig')) : '',
        'timestamp' => $timestamp,
        'description' => format_text($description, FORMAT_HTML, ['context' => $context]),
        'hasdescription' => trim($description) !== '' && trim($description) !== ' ',
        'contenttype' => $contenttype,
        'ispopup' => $contenttype === 'popup',
    ];
}

$firstevent = $clientevents[0] ?? null;

$templatedata = [
    'name' => format_string($timeline->name, true, ['context' => $context]),
    'events' => $clientevents,
    'eventsjson' => json_encode($clientevents, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
    'firstevent' => $firstevent,
    'instanceid' => $timeline->id,
    'noevents' => get_string('noevents', 'mod_timeline'),
    'openlink' => get_string('openlink', 'mod_timeline'),
];

$event = \mod_timeline\event\course_module_viewed::create([
    'objectid' => $timeline->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('timeline', $timeline);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->requires->css('/mod/timeline/styles.css');
$PAGE->requires->js('/mod/timeline/module.js');

echo $OUTPUT->header();
echo '<div class="activity-header"><h2>' . format_string($timeline->name) . '</h2></div>';
if (!empty($timeline->intro)) {
    echo '<div class="activity-description">' . format_module_intro('timeline', $timeline, $cm->id) . '</div>';
}
echo $OUTPUT->render_from_template('mod_timeline/view', $templatedata);
echo $OUTPUT->footer();
