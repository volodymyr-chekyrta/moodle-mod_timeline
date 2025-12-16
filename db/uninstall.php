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
 * Timeline uninstall script.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstall function for mod_timeline.
 *
 * @return bool
 */
function xmldb_timeline_uninstall() {
    global $DB;

    // Delete all files associated with timeline module.
    $fs = get_file_storage();
    
    // Get all timeline module contexts.
    $sql = "SELECT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
              JOIN {modules} m ON m.id = cm.module
             WHERE m.name = :modname";
    
    $contexts = $DB->get_records_sql($sql, [
        'contextlevel' => CONTEXT_MODULE,
        'modname' => 'timeline'
    ]);
    
    foreach ($contexts as $context) {
        $fs->delete_area_files($context->id, 'mod_timeline');
    }
    
    return true;
}
