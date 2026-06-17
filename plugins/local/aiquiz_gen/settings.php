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
 * Settings for the AI Quiz Gen AI Quiz Generator plugin.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page.
    $settings = new admin_settingpage('local_aiquiz_gen', get_string('pluginname', 'local_aiquiz_gen'));

    // Check gateway availability (guard against autoload failure during install/upgrade).
    $gatewayready = false;
    if (class_exists('\local_aiquiz_gen\gateway_client')) {
        $gatewayready = \local_aiquiz_gen\gateway_client::is_ready();
    }

    // Show warning if gateway is not configured.
    if (!$gatewayready) {
        $settings->add(new admin_setting_heading(
            'local_aiquiz_gen/gateway_warning',
            '',
            '<div class="alert alert-warning">' . get_string('gateway_warning_msg', 'local_aiquiz_gen') . '</div>'
        ));
    }

    // Gateway Configuration Section.
    $settings->add(new admin_setting_heading(
        'local_aiquiz_gen/gateway_heading',
        get_string('gateway_heading', 'local_aiquiz_gen'),
        get_string('gateway_heading_desc', 'local_aiquiz_gen')
    ));

    // Gateway API Key setting.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiquiz_gen/gatewaykey',
        get_string('gatewaykey', 'local_aiquiz_gen'),
        get_string('gatewaykey_desc', 'local_aiquiz_gen'),
        ''
    ));

    // Settings heading.
    $settings->add(new admin_setting_heading(
        'local_aiquiz_gen/settings_heading',
        get_string('settings', 'local_aiquiz_gen'),
        ''
    ));

    // Enable/disable question types.
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_multichoice',
        get_string('enable_multichoice', 'local_aiquiz_gen'),
        get_string('enable_multichoice_desc', 'local_aiquiz_gen'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_truefalse',
        get_string('enable_truefalse', 'local_aiquiz_gen'),
        get_string('enable_truefalse_desc', 'local_aiquiz_gen'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_shortanswer',
        get_string('enable_shortanswer', 'local_aiquiz_gen'),
        get_string('enable_shortanswer_desc', 'local_aiquiz_gen'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_essay',
        get_string('enable_essay', 'local_aiquiz_gen'),
        get_string('enable_essay_desc', 'local_aiquiz_gen'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_matching',
        get_string('enable_matching', 'local_aiquiz_gen'),
        get_string('enable_matching_desc', 'local_aiquiz_gen'),
        0  // Disabled by default as it's more complex.
    ));

    // Maximum questions per request.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/max_questions_per_request',
        get_string('max_questions_per_request', 'local_aiquiz_gen'),
        get_string('max_questions_per_request_desc', 'local_aiquiz_gen'),
        100,
        PARAM_INT
    ));

    // Maximum file upload size.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/max_file_size_mb',
        get_string('max_file_size_mb', 'local_aiquiz_gen'),
        get_string('max_file_size_mb_desc', 'local_aiquiz_gen'),
        50,
        PARAM_INT
    ));

    // Maximum number of activities per extraction.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/max_activities',
        get_string('max_activities', 'local_aiquiz_gen'),
        get_string('max_activities_desc', 'local_aiquiz_gen'),
        20,
        PARAM_INT
    ));

    // Maximum total content size for extraction (MB).
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/max_extraction_size_mb',
        get_string('max_extraction_size_mb', 'local_aiquiz_gen'),
        get_string('max_extraction_size_mb_desc', 'local_aiquiz_gen'),
        100,
        PARAM_INT
    ));

    // Default quality mode.
    $settings->add(new admin_setting_configselect(
        'local_aiquiz_gen/default_quality_mode',
        get_string('default_quality_mode', 'local_aiquiz_gen'),
        get_string('default_quality_mode_desc', 'local_aiquiz_gen'),
        'balanced',
        [
            'fast' => get_string('quality_fast', 'local_aiquiz_gen'),
            'balanced' => get_string('quality_balanced', 'local_aiquiz_gen'),
            'best' => get_string('quality_best', 'local_aiquiz_gen'),
        ]
    ));

    // Cleanup old requests.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/cleanup_days',
        get_string('cleanup_days', 'local_aiquiz_gen'),
        get_string('cleanup_days_desc', 'local_aiquiz_gen'),
        90,
        PARAM_INT
    ));

    // Content hash deduplication.
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_content_deduplication',
        get_string('enable_content_deduplication', 'local_aiquiz_gen'),
        get_string('enable_content_deduplication_desc', 'local_aiquiz_gen'),
        1 // Enabled by default.
    ));

    // Question validation.
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_question_validation',
        get_string('enable_question_validation', 'local_aiquiz_gen'),
        get_string('enable_question_validation_desc', 'local_aiquiz_gen'),
        1 // Enabled by default.
    ));

    // Phase 6: Production Hardening Settings.
    $settings->add(new admin_setting_heading(
        'local_aiquiz_gen/production_heading',
        get_string('production_heading', 'local_aiquiz_gen'),
        ''
    ));

    // Enable caching.
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_caching',
        get_string('enable_caching', 'local_aiquiz_gen'),
        get_string('enable_caching_desc', 'local_aiquiz_gen'),
        1 // Enabled by default.
    ));

    // Enable rate limiting.
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquiz_gen/enable_rate_limiting',
        get_string('enable_rate_limiting', 'local_aiquiz_gen'),
        get_string('enable_rate_limiting_desc', 'local_aiquiz_gen'),
        1 // Enabled by default.
    ));

    // Rate limit per hour (per user).
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/rate_limit_per_hour',
        get_string('rate_limit_per_hour', 'local_aiquiz_gen'),
        get_string('rate_limit_per_hour_desc', 'local_aiquiz_gen'),
        10,
        PARAM_INT
    ));

    // Rate limit per day (per user).
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/rate_limit_per_day',
        get_string('rate_limit_per_day', 'local_aiquiz_gen'),
        get_string('rate_limit_per_day_desc', 'local_aiquiz_gen'),
        50,
        PARAM_INT
    ));

    // Site-wide rate limit per hour.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/site_rate_limit_per_hour',
        get_string('site_rate_limit_per_hour', 'local_aiquiz_gen'),
        get_string('site_rate_limit_per_hour_desc', 'local_aiquiz_gen'),
        200,
        PARAM_INT
    ));

    // Health check token.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/health_check_token',
        get_string('health_check_token', 'local_aiquiz_gen'),
        get_string('health_check_token_desc', 'local_aiquiz_gen'),
        bin2hex(random_bytes(16)), // Generate random token.
        PARAM_ALPHANUMEXT
    ));

    // Maximum regenerations per question.
    $settings->add(new admin_setting_configtext(
        'local_aiquiz_gen/max_regenerations',
        get_string('max_regenerations', 'local_aiquiz_gen'),
        get_string('max_regenerations_desc', 'local_aiquiz_gen'),
        5,
        PARAM_INT
    ));

    // PDF extraction tool paths.
    $settings->add(new admin_setting_heading(
        'local_aiquiz_gen/pdftools_heading',
        get_string('pdftools_heading', 'local_aiquiz_gen'),
        get_string('pdftools_heading_desc', 'local_aiquiz_gen')
    ));

    $settings->add(new admin_setting_configexecutable(
        'local_aiquiz_gen/pathtopdftotext',
        get_string('pathtopdftotext', 'local_aiquiz_gen'),
        get_string('pathtopdftotext_desc', 'local_aiquiz_gen'),
        ''
    ));

    $settings->add(new admin_setting_configexecutable(
        'local_aiquiz_gen/pathtogs',
        get_string('pathtogs', 'local_aiquiz_gen'),
        get_string('pathtogs_desc', 'local_aiquiz_gen'),
        ''
    ));

    // Add settings page directly to localplugins (like aiquiz_grading).
    $ADMIN->add('localplugins', $settings);

    // Add admin dashboard page directly to localplugins.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_aiquiz_gen_admin',
        get_string('admin_dashboard', 'local_aiquiz_gen'),
        new moodle_url('/local/aiquiz_gen/admin_dashboard.php'),
        'moodle/site:config'
    ));
}
