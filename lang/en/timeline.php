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
 * Language strings for the Interactive Timeline activity.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Interactive Timeline';
$string['modulename'] = 'Interactive Timeline';
$string['modulenameplural'] = 'Interactive Timelines';
$string['pluginadministration'] = 'Timeline administration';

$string['timeline:addinstance'] = 'Add a new Interactive Timeline activity';
$string['timeline:view'] = 'View Interactive Timeline';

$string['name'] = 'Timeline name';
$string['intro'] = 'Intro';
$string['display'] = 'Display settings';
$string['displaymode'] = 'Display mode';
$string['displaymode_help'] = 'Choose how to display the timeline: horizontal (left to right) or vertical (top to bottom)';
$string['displaymode_horizontal'] = 'Horizontal';
$string['displaymode_vertical'] = 'Vertical';
$string['events'] = 'Timeline events';
$string['eventtitle'] = 'Event title';
$string['eventdate'] = 'Event date';
$string['eventshortdesc'] = 'Short description';
$string['eventdescription'] = 'Description';
$string['addevent'] = 'Add another event';
$string['defaultheading'] = 'Timeline overview';
$string['noevents'] = 'No events have been added yet.';
$string['eventdetails'] = 'Event details';
$string['openlink'] = 'Open link';
$string['removeevent'] = 'Remove event';
$string['eventcontenttype'] = 'Content display type';
$string['contenttype_default'] = 'Default (tab)';
$string['contenttype_popup'] = 'Popup (modal)';
$string['privacy:metadata'] = 'The Interactive Timeline plugin stores event details configured by instructors.';
$string['validation:noevents'] = 'Add at least one event with a date and title.';
$string['validation:missingdate'] = 'Provide a date for every titled event.';
$string['validation:missingtitle'] = 'Provide a title for every dated event.';
