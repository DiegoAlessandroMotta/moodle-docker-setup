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
 * Hook callbacks for the AI Quiz Generator plugin.
 *
 * Replaces the legacy `local_<plugin>_before_http_headers()` function in
 * lib.php. Moodle 5.2 introduced a PSR-14 hook system; legacy callbacks
 * still work but trigger a deprecation warning on every page that calls
 * them. New code should subscribe via db/hooks.php + a static method here.
 *
 * @package    local_aiquiz_gen
 * @copyright  2026 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiquiz_gen;

/**
 * Static hook handlers for core\hook\output\* events.
 *
 * Method names are arbitrary; the binding to a hook happens in db/hooks.php.
 * Moodle's hook manager reflects on the typed parameter to decide which
 * hooks a given method can satisfy, so the type hint is required.
 */
class hook_callbacks {

    /**
     * Ensure Font Awesome is available on every page that uses this plugin.
     *
     * The plugin's templates and AMD modules reference FA icons directly,
     * so the asset must be loaded before the renderer flushes the head.
     *
     * Migrated from the legacy `local_aiquiz_gen_before_http_headers()`
     * function in lib.php, which triggered a "should be migrated" warning
     * on every page in Moodle 5.2.
     *
     * @param \core\hook\output\before_http_headers $hook The dispatched hook.
     * @return void
     */
    public static function ensure_fontawesome(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        // Guard for safety on custom builds / unusual PAGE states (CLI, AJAX,
        // install wizard). The method is added by moodle_page in 4.1+; if it
        // is not present, the platform will not have a font awesome handler
        // to invoke anyway, so we silently no-op.
        if (!empty($PAGE) && method_exists($PAGE->requires, 'fontawesome')) {
            $PAGE->requires->fontawesome();
        }
    }
}
