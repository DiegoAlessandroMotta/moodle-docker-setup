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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library functions for the AI Quiz Gen AI Quiz Generator plugin.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add AI Quiz Generator link to course navigation.
 *
 * @param navigation_node $navigation Navigation node
 * @param stdClass $course Course object
 * @param context_course $context Course context
 * @return void
 */
function local_aiquiz_gen_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    if (has_capability('local/aiquiz_gen:generatequestions', $context)) {
        // AI Quiz Generator (Wizard).
        $navigation->add(
            get_string('navtitle', 'local_aiquiz_gen'),
            new moodle_url('/local/aiquiz_gen/wizard.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_aiquiz_gen_wizard'
        );

        // AI Quiz Dashboard.
        $navigation->add(
            get_string('dashboard', 'local_aiquiz_gen'),
            new moodle_url('/local/aiquiz_gen/index.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_aiquiz_gen_dashboard'
        );
    }
}

/**
 * Serve plugin files.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context
 * @param string $filearea File area
 * @param array $args Extra arguments
 * @param bool $forcedownload Force download
 * @param array $options Additional options
 * @return bool False if file not found, does not return if found
 */
function local_aiquiz_gen_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    // Check context is course context.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // Check user has capability.
    require_capability('local/aiquiz_gen:generatequestions', $context);

    // Only support 'content' file area (uploaded course content).
    if ($filearea !== 'content') {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_aiquiz_gen/$filearea/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Send the file.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Get uploaded files data from the request in a safe wrapper.
 *
 * Wraps PHP's $_FILES superglobal access to centralise validation
 * and satisfy Moodle coding-style requirements (no direct superglobal access).
 *
 * @param string $fieldname The form field name for the uploaded file(s).
 * @return array|null The file upload data array, or null if no file was uploaded.
 */
function local_aiquiz_gen_get_uploaded_files(string $fieldname): ?array {
    // phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalGlobalState -- File uploads require $_FILES.
    if (empty($_FILES[$fieldname]) || empty($_FILES[$fieldname]['name'])) {
        return null;
    }
    return $_FILES[$fieldname];
}

/**
 * Get a single uploaded file from the request in a safe wrapper.
 *
 * @param string $fieldname The form field name for the uploaded file.
 * @return array|null The file upload data array, or null if no file was uploaded.
 */
function local_aiquiz_gen_get_uploaded_file(string $fieldname): ?array {
    // phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalGlobalState -- File uploads require $_FILES.
    if (empty($_FILES[$fieldname])) {
        return null;
    }
    $file = $_FILES[$fieldname];
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    return $file;
}
