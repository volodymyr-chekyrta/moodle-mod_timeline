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
 * Library of functions and constants for the Interactive Timeline activity.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns features supported by the timeline module.
 *
 * @param string $feature Requested feature.
 * @return mixed
 */
function timeline_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_OTHER,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_BACKUP_MOODLE2 => true,
        default => null,
    };
}

/**
 * Add a new timeline instance.
 *
 * @param stdClass $data Form data object
 * @param mod_timeline_mod_form|null $mform Form object (optional)
 * @return int New timeline instance ID
 */
function timeline_add_instance($data, $mform = null) {
    global $DB, $USER;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    
    // Serialize events first (without file processing)
    $data->eventsjson = timeline_serialize_events($data);

    $id = $DB->insert_record('timeline', $data);

    // Set instance in course_modules
    $DB->set_field('course_modules', 'instance', $id, ['id' => $data->coursemodule]);
    $context = context_module::instance($data->coursemodule);

    // Now process editor files for each event description
    if ($mform && isset($data->eventdescription) && is_array($data->eventdescription)) {
        $events = json_decode($data->eventsjson, true) ?: [];
        $updated = false;
        
        // Build a map of originalindex => event for quick lookup
        $eventsByOriginalIndex = [];
        foreach ($events as $i => $event) {
            if (isset($event['originalindex'])) {
                $eventsByOriginalIndex[$event['originalindex']] = $i;
            }
        }
        
        foreach ($data->eventdescription as $index => $description) {
            if (is_array($description) && isset($eventsByOriginalIndex[$index])) {
                $eventIndex = $eventsByOriginalIndex[$index];
                $text = isset($description['text']) ? $description['text'] : '';
                $itemid = isset($description['itemid']) ? $description['itemid'] : 0;
                
                if ($itemid > 0) {
                    // If text is empty but files are uploaded, generate img tags
                    if (trim($text) === '' || trim($text) === '&nbsp;') {
                        $fs = get_file_storage();
                        $usercontext = context_user::instance($USER->id);
                        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid, 'filename', false);
                        
                        if (!empty($draftfiles)) {
                            $text = '';
                            foreach ($draftfiles as $file) {
                                $filename = $file->get_filename();
                                $text .= '<p><img src="@@PLUGINFILE@@/' . $filename . '" alt="' . $filename . '"></p>';
                            }
                        } else if (trim($text) === '') {
                            // No files but itemid exists - add space to prevent empty field
                            $text = ' ';
                        }
                    }
                    
                    $savedtext = file_save_draft_area_files(
                        $itemid,
                        $context->id,
                        'mod_timeline',
                        'eventdescription',
                        $index,
                        ['subdirs' => false, 'maxfiles' => 10],
                        $text
                    );
                    $events[$eventIndex]['description'] = $savedtext;
                    $updated = true;
                }
            }
        }
            
            if ($updated) {
                $data->eventsjson = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
                $DB->set_field('timeline', 'eventsjson', $data->eventsjson, ['id' => $id]);
            }
        }
        
        if (!empty($data->completionexpected)) {
            \core_completion\api::update_completion_date_event($data->coursemodule, 'timeline', $id, $data->completionexpected);
        }
        
        return $id;
    }/**
 * Update an existing timeline instance.
 *
 * @param stdClass $data Form data object
 * @param mod_timeline_mod_form|null $mform Form object (optional)
 * @return bool True on success
 */
function timeline_update_instance($data, $mform = null) {
    global $DB, $USER;

    $data->id = $data->instance;
    $data->timemodified = time();
    
    $context = context_module::instance($data->coursemodule);
    
    // Serialize events first
    $data->eventsjson = timeline_serialize_events($data);
    
    // Process editor files for each event description
    if ($mform && isset($data->eventdescription) && is_array($data->eventdescription)) {
        $events = json_decode($data->eventsjson, true) ?: [];
        $updated = false;
        
        // Build a map of originalindex => event for quick lookup
        $eventsByOriginalIndex = [];
        foreach ($events as $i => $event) {
            if (isset($event['originalindex'])) {
                $eventsByOriginalIndex[$event['originalindex']] = $i;
            }
        }
        
        foreach ($data->eventdescription as $index => $description) {
            if (is_array($description) && isset($eventsByOriginalIndex[$index])) {
                $eventIndex = $eventsByOriginalIndex[$index];
                $text = isset($description['text']) ? $description['text'] : '';
                $itemid = isset($description['itemid']) ? $description['itemid'] : 0;
                
                if ($itemid > 0) {
                    // If text is empty but files are uploaded, generate img tags
                    if (trim($text) === '' || trim($text) === '&nbsp;') {
                        $fs = get_file_storage();
                        $usercontext = context_user::instance($USER->id);
                        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid, 'filename', false);
                        
                        if (!empty($draftfiles)) {
                            $text = '';
                            foreach ($draftfiles as $file) {
                                $filename = $file->get_filename();
                                $text .= '<p><img src="@@PLUGINFILE@@/' . $filename . '" alt="' . $filename . '"></p>';
                            }
                        } else if (trim($text) === '') {
                            // No files but itemid exists - add space to prevent empty field
                            $text = ' ';
                        }
                    }
                    
                    $savedtext = file_save_draft_area_files(
                        $itemid,
                        $context->id,
                        'mod_timeline',
                        'eventdescription',
                        $index,
                        ['subdirs' => false, 'maxfiles' => 10],
                        $text
                    );
                    $events[$eventIndex]['description'] = $savedtext;
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            $data->eventsjson = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        }
    }

    if (!empty($data->completionexpected)) {
        \core_completion\api::update_completion_date_event(
            $data->coursemodule,
            'timeline',
            $data->id,
            $data->completionexpected
        );
    } else {
        \core_completion\api::update_completion_date_event($data->coursemodule, 'timeline', $data->id, null);
    }

    return $DB->update_record('timeline', $data);
}

/**
 * Delete a timeline instance.
 *
 * @param int $id Timeline instance ID
 * @return bool True on success, false if not found
 */
function timeline_delete_instance($id) {
    global $DB;

    if (!$timeline = $DB->get_record('timeline', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('timeline', ['id' => $timeline->id]);

    return true;
}

/**
 * Returns data to be displayed on the course page.
 *
 * @param cm_info $coursemodule Course module object
 * @return cached_cm_info|null Info object for display on course page
 */
function timeline_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat';
    if (!$timeline = $DB->get_record('timeline', ['id' => $coursemodule->instance], $fields)) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $timeline->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('timeline', $timeline, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Reset user data hook.
 *
 * @param stdClass $data The data submitted from the reset course form
 * @return array Status array
 */
function timeline_reset_userdata($data) {
    return [];
}

/**
 * Convert form data into a stable, sorted JSON payload.
 *
 * Creates a JSON array of timeline events sorted by timestamp.
 * Schema:
 *  - uid (string): Unique identifier
 *  - timestamp (int): Unix timestamp
 *  - title (string): Event title
 *  - shortdesc (string): Short description
 *  - description (string): Full description with HTML
 *  - contenttype (string): 'default' or 'popup'
 *  - originalindex (int): Original form index (for file associations)
 *
 * @param stdClass $data Form data object containing event arrays
 * @return string JSON-encoded events array
 */
function timeline_serialize_events(stdClass $data): string {
    global $USER, $CFG;
    
    $events = [];

    $titles = $data->eventtitle ?? [];
    $dates = $data->eventdate ?? [];
    $shortdescs = $data->eventshortdesc ?? [];
    $descriptions = $data->eventdescription ?? [];
    $contenttypes = $data->eventcontenttype ?? [];
    $uids = $data->eventuid ?? [];
    $deleteds = $data->eventdeleted ?? [];

    if (!is_array($titles)) {
        return json_encode([], JSON_UNESCAPED_UNICODE);
    }

    foreach ($titles as $index => $unused) {
        // Skip deleted events.
        if (!empty($deleteds[$index])) {
            continue;
        }

        $title = trim((string)($titles[$index] ?? ''));
        
        // Convert date_selector format to timestamp
        $timestamp = 0;
        $dateValue = $dates[$index] ?? null;
        if (is_array($dateValue)) {
            // Format: ['day' => ..., 'month' => ..., 'year' => ...]
            $day = (int)($dateValue['day'] ?? 0);
            $month = (int)($dateValue['month'] ?? 0);
            $year = (int)($dateValue['year'] ?? 0);
            if ($day && $month && $year) {
                $timestamp = mktime(0, 0, 0, $month, $day, $year);
            }
        } else if ($dateValue) {
            // Fallback for numeric timestamp
            $timestamp = (int)$dateValue;
        }
        
        // Handle description - can be string or editor array ['text' => ..., 'format' => ...]
        $description = '';
        $descValue = $descriptions[$index] ?? null;
        if (is_array($descValue)) {
            $description = trim((string)($descValue['text'] ?? ''));
        } else {
            $description = trim((string)$descValue);
        }
        
        $shortdesc = trim((string)($shortdescs[$index] ?? ''));

        // Skip fully empty rows - but don't check description as it may contain only files
        if ($title === '' && $timestamp === 0 && $shortdesc === '') {
            continue;
        }

        $uid = trim((string)($uids[$index] ?? ''));
        if ($uid === '') {
            $uid = 'uid' . uniqid();
        }
        
        $contenttype = trim((string)($contenttypes[$index] ?? 'default'));
        if (!in_array($contenttype, ['default', 'popup'])) {
            $contenttype = 'default';
        }

        $events[] = [
            'uid' => $uid,
            'timestamp' => $timestamp,
            'title' => $title,
            'shortdesc' => $shortdesc,
            'description' => $description,
            'contenttype' => $contenttype,
            'originalindex' => $index,
        ];
    }

    if (!empty($events)) {
        usort($events, static function($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });
    }

    // HEX_TAG reduces risk if JSON ever gets embedded into HTML.
    return json_encode($events, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}

/**
 * Decode stored events as a PHP array with backward compatibility.
 *
 * Converts JSON string to array and ensures all required fields exist.
 * Provides backward compatibility for old 'date' field.
 *
 * @param string|null $json JSON-encoded events string
 * @return array Array of event objects
 */
function timeline_decode_events(?string $json): array {
    if (empty($json)) {
        return [];
    }

    $events = json_decode($json, true);
    if (!is_array($events)) {
        return [];
    }

    foreach ($events as $i => $event) {
        if (!is_array($event)) {
            $events[$i] = [];
            continue;
        }

        // Backward compatibility for old schema: 'date' -> 'timestamp'.
        if (!isset($event['timestamp']) && isset($event['date'])) {
            $event['timestamp'] = (int)$event['date'];
        }

        if (!isset($event['uid']) || trim((string)$event['uid']) === '') {
            $event['uid'] = 'uid' . uniqid();
        }

        $event['timestamp'] = !empty($event['timestamp']) ? (int)$event['timestamp'] : 0;
        $event['title'] = (string)($event['title'] ?? '');
        $event['description'] = (string)($event['description'] ?? '');
        $event['media'] = (string)($event['media'] ?? '');

        $events[$i] = $event;
    }

    return $events;
}

/**
 * Actions counted as views in participation reports.
 *
 * @return array List of view action names
 */
function timeline_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * Actions counted as posts in participation reports.
 *
 * @return array List of post action names
 */
function timeline_get_post_actions() {
    return ['add', 'update'];
}

/**
 * Serve the files from the timeline file areas.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function timeline_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea !== 'eventdescription') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_timeline', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}
