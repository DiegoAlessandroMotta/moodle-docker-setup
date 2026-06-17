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
 * External services definitions for the AI Quiz Generator plugin.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    // Dashboard statistics endpoints.
    'local_aiquiz_gen_get_dashboard_stats' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_dashboard_stats',
        'description' => 'Get dashboard statistics for the current user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_question_type_distribution' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_question_type_distribution',
        'description' => 'Get question type distribution for charts',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_difficulty_distribution' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_difficulty_distribution',
        'description' => 'Get difficulty level distribution',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_blooms_distribution' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_blooms_distribution',
        'description' => 'Get Bloom\'s taxonomy level distribution',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_acceptance_trend' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_acceptance_trend',
        'description' => 'Get acceptance rate trend over recent generations',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_regeneration_by_type' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_regeneration_by_type',
        'description' => 'Get regeneration statistics by question type',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_quality_distribution' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_quality_distribution',
        'description' => 'Get quality score distribution histogram data',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_recent_requests' => [
        'classname' => 'local_aiquiz_gen\external\dashboard_external',
        'methodname' => 'get_recent_requests',
        'description' => 'Get recent quiz generation requests',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // Question action endpoints.
    'local_aiquiz_gen_update_question' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'update_question',
        'description' => 'Update a question field inline',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_update_answer' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'update_answer',
        'description' => 'Update an answer field inline',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_reorder_answers' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'reorder_answers',
        'description' => 'Reorder answers for a question',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_approve_question' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'approve_question',
        'description' => 'Approve a generated question',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_reject_question' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'reject_question',
        'description' => 'Reject a generated question',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_bulk_approve' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'bulk_approve',
        'description' => 'Bulk approve multiple questions',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_bulk_reject' => [
        'classname' => 'local_aiquiz_gen\external\question_external',
        'methodname' => 'bulk_reject',
        'description' => 'Bulk reject multiple questions',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // Topic management endpoints.
    'local_aiquiz_gen_update_topic' => [
        'classname' => 'local_aiquiz_gen\external\topic_external',
        'methodname' => 'update_topic',
        'description' => 'Update a topic title',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_merge_topics' => [
        'classname' => 'local_aiquiz_gen\external\topic_external',
        'methodname' => 'merge_topics',
        'description' => 'Merge two topics into one',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_delete_topic' => [
        'classname' => 'local_aiquiz_gen\external\topic_external',
        'methodname' => 'delete_topic',
        'description' => 'Delete a topic and its questions',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // Analytics endpoints.
    'local_aiquiz_gen_get_course_analytics' => [
        'classname' => 'local_aiquiz_gen\external\analytics_external',
        'methodname' => 'get_course_analytics',
        'description' => 'Get analytics data for a specific course',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:viewreports',
    ],
    'local_aiquiz_gen_get_teacher_confidence' => [
        'classname' => 'local_aiquiz_gen\external\analytics_external',
        'methodname' => 'get_teacher_confidence',
        'description' => 'Get teacher confidence trend data',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:viewreports',
    ],

    // Wizard state endpoints.
    'local_aiquiz_gen_get_progress' => [
        'classname' => 'local_aiquiz_gen\external\wizard_external',
        'methodname' => 'get_progress',
        'description' => 'Get generation progress for a request',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_save_wizard_state' => [
        'classname' => 'local_aiquiz_gen\external\wizard_external',
        'methodname' => 'save_wizard_state',
        'description' => 'Save wizard state for session resumption',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_wizard_state' => [
        'classname' => 'local_aiquiz_gen\external\wizard_external',
        'methodname' => 'get_wizard_state',
        'description' => 'Get saved wizard state',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_clear_wizard_state' => [
        'classname' => 'local_aiquiz_gen\external\wizard_external',
        'methodname' => 'clear_wizard_state',
        'description' => 'Clear wizard state',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // File management endpoints.
    'local_aiquiz_gen_upload_file' => [
        'classname' => 'local_aiquiz_gen\external\file_external',
        'methodname' => 'upload_file',
        'description' => 'Upload a file for content extraction',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_remove_file' => [
        'classname' => 'local_aiquiz_gen\external\file_external',
        'methodname' => 'remove_file',
        'description' => 'Remove an uploaded file',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // Template endpoints.
    'local_aiquiz_gen_save_template' => [
        'classname' => 'local_aiquiz_gen\external\template_external',
        'methodname' => 'save_template',
        'description' => 'Save current configuration as a template',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_get_templates' => [
        'classname' => 'local_aiquiz_gen\external\template_external',
        'methodname' => 'get_templates',
        'description' => 'Get user saved templates',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_delete_template' => [
        'classname' => 'local_aiquiz_gen\external\template_external',
        'methodname' => 'delete_template',
        'description' => 'Delete a saved template',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],

    // Diagnostic endpoints.
    'local_aiquiz_gen_fix_category' => [
        'classname' => 'local_aiquiz_gen\external\diagnostic_external',
        'methodname' => 'fix_category',
        'description' => 'Fix question category field for deployed questions',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_check_question_types' => [
        'classname' => 'local_aiquiz_gen\external\diagnostic_external',
        'methodname' => 'check_question_types',
        'description' => 'Check question type data integrity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_repair_questions' => [
        'classname' => 'local_aiquiz_gen\external\diagnostic_external',
        'methodname' => 'repair_questions',
        'description' => 'Repair questions with missing category data',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
    'local_aiquiz_gen_diagnose' => [
        'classname' => 'local_aiquiz_gen\external\diagnostic_external',
        'methodname' => 'diagnose',
        'description' => 'Diagnose question deployment status',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aiquiz_gen:generatequestions',
    ],
];
