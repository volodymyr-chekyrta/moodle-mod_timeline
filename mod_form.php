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
 * Settings form for the Interactive Timeline activity.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/timeline/lib.php');

/**
 * Activity configuration form.
 */
class mod_timeline_mod_form extends moodleform_mod {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'mod_timeline'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('intro', 'mod_timeline'));

        $mform->addElement('header', 'events', get_string('events', 'mod_timeline'));

        $repeatarray = [];
        $repeatarray[] = $mform->createElement('date_selector', 'eventdate', get_string('eventdate', 'mod_timeline'));
        $repeatarray[] = $mform->createElement('text', 'eventtitle', get_string('eventtitle', 'mod_timeline'), ['size' => 48]);
        
        $repeatarray[] = $mform->createElement('text', 'eventshortdesc', get_string('eventshortdesc', 'mod_timeline'), ['size' => 60]);

        $repeatarray[] = $mform->createElement(
            'editor',
            'eventdescription',
            get_string('eventdescription', 'mod_timeline'),
            ['rows' => 10],
            [
                'maxfiles' => 10,
                'maxbytes' => 0,
                'trusttext' => false,
                'noclean' => false,
                'subdirs' => false,
            ]
        );
        
        $repeatarray[] = $mform->createElement('select', 'eventcontenttype', get_string('eventcontenttype', 'mod_timeline'), [
            'default' => get_string('contenttype_default', 'mod_timeline'),
            'popup' => get_string('contenttype_popup', 'mod_timeline'),
        ]);

        // Hidden fields for deletion logic (not displayed).
        $repeatarray[] = $mform->createElement('hidden', 'eventuid', '');
        $mform->setType('eventuid', PARAM_RAW);

        $repeatarray[] = $mform->createElement('hidden', 'eventdeleted', '0');
        $mform->setType('eventdeleted', PARAM_INT);

        $existing = $this->get_existing_events();
        // When creating: show 1 empty event. When editing: show existing events.
        if (empty($this->_instance)) {
            $repeatno = 1;
        } else {
            $repeatno = !empty($existing) ? count($existing) : 1;
        }

        $repeateloptions = [];
        $repeateloptions['eventtitle']['type'] = PARAM_TEXT;
        $repeateloptions['eventshortdesc']['type'] = PARAM_TEXT;
        $repeateloptions['eventdescription']['type'] = PARAM_RAW;
        $repeateloptions['eventcontenttype']['type'] = PARAM_ALPHA;
        $repeateloptions['eventcontenttype']['default'] = 'default';
        $repeateloptions['eventdeleted']['type'] = PARAM_INT;
        $repeateloptions['eventuid']['type'] = PARAM_RAW;

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeateloptions,
            'event_repeats',
            'addevent',
            1,
            get_string('addevent', 'mod_timeline'),
            true
        );

        // Add remove button for each repeat after repeat_elements renders them.
        $script = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all event repeats by looking for eventtitle fields
    var titleInputs = document.querySelectorAll('input[name^="eventtitle"]');

    if (titleInputs.length === 0) {
        return;
    }

    titleInputs.forEach(function(titleInput) {
        // Extract repeat index from name, e.g., "eventtitle[0]" -> 0
        var match = titleInput.name.match(/\[(\d+)\]/);
        if (!match) return;

        var repeatIndex = match[1];
        
        // Find all fields for this repeat
        var contentTypeSelect = document.querySelector("select[name='eventcontenttype[" + repeatIndex + "]']");
        var deletedInput = document.querySelector("input[name='eventdeleted[" + repeatIndex + "]']");

        if (!titleInput || !deletedInput || !contentTypeSelect) {
            return;
        }

        // Find the LAST fitem for this repeat (eventcontenttype is last visible field)
        var fitem = contentTypeSelect.closest('.fitem');
        if (!fitem) {
            return;
        }
        
        // Add visual separator after each event group
        fitem.style.borderBottom = '3px solid #e3e6ec';
        fitem.style.paddingBottom = '15px';
        fitem.style.marginBottom = '20px';

        // Create remove button
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger btn-sm';
        var eventTitle = titleInput.value.trim() || 'Event ' + (parseInt(repeatIndex) + 1);
        btn.textContent = 'Remove: ' + eventTitle;
        btn.style.marginTop = '15px';
        btn.style.marginBottom = '0';
        btn.style.display = 'block';
        btn.title = 'Delete this event';

        btn.addEventListener('click', function(e) {
            e.preventDefault();

            // Confirm before deletion
            if (!confirm('Are you sure you want to remove this event?')) {
                return;
            }

            // Hide all field fitems for this repeat
            var allFitems = document.querySelectorAll('.fitem');
            allFitems.forEach(function(f) {
                var inputs = f.querySelectorAll('input, textarea, select');
                var shouldHide = false;
                inputs.forEach(function(inp) {
                    // Check both simple names like eventtitle[0] and date names like eventdate[0][day]
                    if (inp.name && (inp.name.includes('[' + repeatIndex + ']') || inp.name.includes('[' + repeatIndex + '][') )) {
                        shouldHide = true;
                    }
                });
                if (shouldHide) {
                    f.style.display = 'none';
                }
            });

            // Set eventdeleted flag
            deletedInput.value = '1';

            // Hide the button
            btn.style.display = 'none';
            
            // Remove separator border from hidden event
            fitem.style.borderBottom = 'none';
            fitem.style.paddingBottom = '0';
            fitem.style.marginBottom = '0';
        });

        // Insert button INSIDE the fitem, at the bottom
        var felement = fitem.querySelector('.felement');
        if (felement) {
            // Add flex layout to the container
            var colMd9 = felement.closest('.col-md-9');
            if (colMd9) {
                colMd9.style.display = 'flex';
                colMd9.style.flexDirection = 'column';
                colMd9.style.justifyContent = 'space-between';
            }
            felement.appendChild(btn);
        } else {
            fitem.appendChild(btn);
        }
    });
});
</script>
SCRIPT;

        $mform->addElement('html', $script);

        // Prevent scroll to top when adding new event
        $scrollScript = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Save scroll position before form submission
    var form = document.querySelector('form.mform');
    if (form) {
        form.addEventListener('submit', function() {
            sessionStorage.setItem('timeline_scroll', window.scrollY);
        });
    }
    
    // Restore scroll position after page reload
    var savedScroll = sessionStorage.getItem('timeline_scroll');
    if (savedScroll !== null) {
        window.scrollTo(0, parseInt(savedScroll));
        sessionStorage.removeItem('timeline_scroll');
    }
});
</script>
SCRIPT;

        $mform->addElement('html', $scrollScript);

        // Client-side required rules only for the first visible row.
        if ($mform->elementExists('eventtitle[0]')) {
            $mform->addRule('eventtitle[0]', get_string('required'), 'required', null, 'client');
            $mform->addRule('eventdate[0]', get_string('required'), 'required', null, 'client');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Preprocess form data.
     *
     * @param array $defaultvalues Default form values
     */
    public function data_preprocessing(&$defaultvalues) {
        if (empty($this->_instance)) {
            return;
        }

        $events = $this->get_existing_events();
        if (empty($events)) {
            return;
        }

        foreach (array_values($events) as $index => $event) {
            $defaultvalues['eventtitle[' . $index . ']'] = $event['title'] ?? '';
            
            // Convert timestamp to date_selector format
            $timestamp = $event['timestamp'] ?? ($event['date'] ?? 0);
            if ($timestamp) {
                // date_selector expects eventdate[index] to be a timestamp, NOT an array
                $defaultvalues['eventdate[' . $index . ']'] = $timestamp;
            }
            
            // Prepare editor field with file handling
            $draftideditor = file_get_submitted_draft_itemid('eventdescription[' . $index . ']');
            $description = $event['description'] ?? '';
            
            // Prepare text with embedded files
            $description = file_prepare_draft_area(
                $draftideditor,
                $this->context->id,
                'mod_timeline',
                'eventdescription',
                $index,
                ['subdirs' => false, 'maxfiles' => 10],
                $description
            );
            
            $defaultvalues['eventdescription[' . $index . ']'] = [
                'text' => $description,
                'format' => FORMAT_HTML,
                'itemid' => $draftideditor,
            ];
            $defaultvalues['eventshortdesc[' . $index . ']'] = $event['shortdesc'] ?? '';
            $defaultvalues['eventcontenttype[' . $index . ']'] = $event['contenttype'] ?? 'default';
            $defaultvalues['eventuid[' . $index . ']'] = $event['uid'] ?? ('uid' . (time() + $index));
            $defaultvalues['eventdeleted[' . $index . ']'] = 0;
        }
    }

    /**
     * Form validation.
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $titles = $data['eventtitle'] ?? [];
        $dates = $data['eventdate'] ?? [];
        $deleteds = $data['eventdeleted'] ?? [];

        $hasvalidevent = false;

        if (is_array($titles)) {
            foreach ($titles as $index => $title) {
                // Skip deleted events.
                if (!empty($deleteds[$index])) {
                    continue;
                }

                $title = trim((string)$title);
                
                // Convert date_selector format to timestamp for validation
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

                // Skip empty rows.
                if ($title === '' && empty($timestamp)) {
                    continue;
                }

                // Title without date is error.
                if ($title !== '' && empty($timestamp)) {
                    $errors["eventdate[{$index}]"] = get_string('validation:missingdate', 'mod_timeline');
                }

                // Date without title is error.
                if ($title === '' && !empty($timestamp)) {
                    $errors["eventtitle[{$index}]"] = get_string('validation:missingtitle', 'mod_timeline');
                }

                // Both present = valid event.
                if ($title !== '' && !empty($timestamp)) {
                    $hasvalidevent = true;
                }
            }
        }

        // At least one valid event required.
        if (!$hasvalidevent) {
            $errors['events'] = get_string('validation:noevents', 'mod_timeline');
        }

        return $errors;
    }

    /**
     * Retrieve events stored for the current instance (normalized).
     *
     * @return array
     */
    private function get_existing_events(): array {
        global $DB;

        if (empty($this->_instance)) {
            return [];
        }

        $record = $DB->get_record('timeline', ['id' => $this->_instance], 'eventsjson');
        if (!$record) {
            return [];
        }

        return timeline_decode_events($record->eventsjson ?? '');
    }
}
