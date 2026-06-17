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
 * Renderable class for the admin dashboard page.
 *
 * Prepares all data for the admin_dashboard Mustache template.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiquiz_gen\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * Admin dashboard page renderable.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_dashboard_page implements renderable, templatable {
    /** @var array Overview statistics. */
    private $overview;

    /** @var array Top courses. */
    private $topcourses;

    /** @var array Top teachers. */
    private $topteachers;

    /** @var array System health data. */
    private $health;

    /**
     * Constructor.
     *
     * @param array $overview Overview statistics.
     * @param array $topcourses Top courses data.
     * @param array $topteachers Top teachers data.
     * @param array $health System health data.
     */
    public function __construct(array $overview, array $topcourses, array $topteachers, array $health) {
        $this->overview = $overview;
        $this->topcourses = $topcourses;
        $this->topteachers = $topteachers;
        $this->health = $health;
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer.
     * @return array Template context data.
     */
    public function export_for_template(renderer_base $output): array {
        $settingsurl = (new moodle_url('/admin/settings.php', ['section' => 'local_aiquiz_gen']))->out(false);
        $capurl = (new moodle_url(
            '/admin/roles/check.php',
            ['capability' => 'local/aiquiz_gen:generatequestions']
        ))->out(false);
        $debuglogsurl = (new moodle_url('/local/aiquiz_gen/debug_logs.php'))->out(false);

        // Top courses with rank.
        $coursesdata = [];
        $rank = 1;
        foreach ($this->topcourses as $course) {
            $coursesdata[] = [
                'rank' => $rank++,
                'fullname' => format_string($course->fullname),
                'questioncount' => get_string(
                    'admin_questions_count',
                    'local_aiquiz_gen',
                    number_format($course->question_count)
                ),
            ];
        }

        // Top teachers with rank.
        $teachersdata = [];
        $rank = 1;
        foreach ($this->topteachers as $teacher) {
            $teachersdata[] = [
                'rank' => $rank++,
                'fullname' => fullname($teacher),
                'fractiontext' => get_string(
                    'admin_approved_fraction',
                    'local_aiquiz_gen',
                    (object)[
                        'approved' => $teacher->approved_questions,
                        'total' => $teacher->total_questions,
                    ]
                ),
                'acceptancerate' => $teacher->acceptance_rate,
            ];
        }

        return [
            // Title.
            'admin_site_analytics_title' => get_string('admin_site_analytics_title', 'local_aiquiz_gen'),
            'admin_site_analytics_subtitle' => get_string('admin_site_analytics_subtitle', 'local_aiquiz_gen'),

            // Overview section.
            'admin_site_overview' => get_string('admin_site_overview', 'local_aiquiz_gen'),
            'admin_total_questions_generated' => get_string('admin_total_questions_generated', 'local_aiquiz_gen'),
            'totalquestionsgenerated' => number_format($this->overview['totalquestionsgenerated']),
            'admin_total_quizzes_created' => get_string('admin_total_quizzes_created', 'local_aiquiz_gen'),
            'totalquizzescreated' => number_format($this->overview['totalquizzescreated']),
            'admin_active_teachers' => get_string('admin_active_teachers', 'local_aiquiz_gen'),
            'activeteachers' => number_format($this->overview['activeteachers']),
            'admin_adoption_rate' => get_string('admin_adoption_rate', 'local_aiquiz_gen', $this->overview['adoptionrate']),
            'admin_courses_using_plugin' => get_string('admin_courses_using_plugin', 'local_aiquiz_gen'),
            'activecourses' => number_format($this->overview['activecourses']),
            'admin_course_coverage' => get_string('admin_course_coverage', 'local_aiquiz_gen', $this->overview['coursecoverage']),
            'avg_quality_score' => get_string('avg_quality_score', 'local_aiquiz_gen'),
            'avgqualityscore' => $this->overview['avgqualityscore'],
            'admin_site_wide_ftar' => get_string('admin_site_wide_ftar', 'local_aiquiz_gen'),
            'siteftar' => $this->overview['siteftar'],
            'first_time_acceptance_rate' => get_string('first_time_acceptance_rate', 'local_aiquiz_gen'),

            // Adoption & Usage.
            'admin_adoption_usage' => get_string('admin_adoption_usage', 'local_aiquiz_gen'),
            'admin_usage_trend' => get_string('admin_usage_trend', 'local_aiquiz_gen'),
            'admin_adoption_overview' => get_string('admin_adoption_overview', 'local_aiquiz_gen'),

            // Quality overview.
            'admin_quality_overview' => get_string('admin_quality_overview', 'local_aiquiz_gen'),
            'blooms_taxonomy' => get_string('blooms_taxonomy', 'local_aiquiz_gen'),
            'admin_question_type_popularity' => get_string('admin_question_type_popularity', 'local_aiquiz_gen'),
            'difficulty_distribution' => get_string('difficulty_distribution', 'local_aiquiz_gen'),

            // Top performers.
            'admin_top_performers' => get_string('admin_top_performers', 'local_aiquiz_gen'),
            'admin_top_courses' => get_string('admin_top_courses', 'local_aiquiz_gen'),
            'hastopcourses' => !empty($this->topcourses),
            'topcourses' => $coursesdata,
            'admin_no_course_data' => get_string('admin_no_course_data', 'local_aiquiz_gen'),
            'admin_top_teachers' => get_string('admin_top_teachers', 'local_aiquiz_gen'),
            'hastopteachers' => !empty($this->topteachers),
            'topteachers' => $teachersdata,
            'admin_no_teacher_data' => get_string('admin_no_teacher_data', 'local_aiquiz_gen'),

            // System health.
            'admin_system_health' => get_string('admin_system_health', 'local_aiquiz_gen'),
            'admin_ai_provider_status' => get_string('admin_ai_provider_status', 'local_aiquiz_gen'),
            'aiproviderconfigured' => $this->health['aiproviderconfigured'],
            'admin_connected' => get_string('admin_connected', 'local_aiquiz_gen'),
            'admin_not_configured' => get_string('admin_not_configured', 'local_aiquiz_gen'),
            'admin_pending_generations' => get_string('admin_pending_generations', 'local_aiquiz_gen'),
            'pendinggenerations' => $this->health['pendinggenerations'],
            'admin_recent_errors' => get_string('admin_recent_errors', 'local_aiquiz_gen'),
            'recenterrors' => $this->health['recenterrors'],
            'haserrors' => $this->health['recenterrors'] > 0,
            'admin_total_failed' => get_string('admin_total_failed', 'local_aiquiz_gen'),
            'failedgenerations' => $this->health['failedgenerations'],

            // Config links.
            'admin_quick_config_links' => get_string('admin_quick_config_links', 'local_aiquiz_gen'),
            'settingsurl' => $settingsurl,
            'admin_plugin_settings' => get_string('admin_plugin_settings', 'local_aiquiz_gen'),
            'capurl' => $capurl,
            'admin_user_capabilities' => get_string('admin_user_capabilities', 'local_aiquiz_gen'),
            'debuglogsurl' => $debuglogsurl,
            'admin_view_error_logs' => get_string('admin_view_error_logs', 'local_aiquiz_gen'),
        ];
    }
}
