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
 * Renderable class for the debug logs page.
 *
 * Prepares all data for the debug_logs Mustache templates.
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
use local_aiquiz_gen\debug_logger;

/**
 * Debug logs page renderable.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debug_logs_page implements renderable, templatable {
    /** @var string Active tab. */
    private $tab;

    /** @var int|null Request ID filter. */
    private $requestid;

    /** @var string Log level filter. */
    private $level;

    /** @var int Limit. */
    private $limit;

    /**
     * Constructor.
     *
     * @param string $tab Active tab name.
     * @param int|null $requestid Request ID filter.
     * @param string $level Level filter.
     * @param int $limit Result limit.
     */
    public function __construct(string $tab, ?int $requestid, string $level, int $limit) {
        $this->tab = $tab;
        $this->requestid = $requestid;
        $this->level = $level;
        $this->limit = $limit;
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer.
     * @return array Template context data.
     */
    public function export_for_template(renderer_base $output): array {
        $data = [
            'provider' => $this->get_provider_data(),
            'tabs' => $this->get_tabs_data(),
            'actions' => $this->get_actions_data(),
            'show_database' => ($this->tab === 'database'),
            'show_file' => ($this->tab === 'file'),
            'show_requests' => ($this->tab === 'requests'),
            'show_system' => ($this->tab === 'system'),
        ];

        // Add tab-specific data.
        switch ($this->tab) {
            case 'database':
                $data['databaselogs'] = $this->get_database_logs_data();
                break;
            case 'file':
                $data['filelogs'] = $this->get_file_logs_data();
                break;
            case 'requests':
                $data['requests'] = $this->get_requests_data();
                break;
            case 'system':
                $data['systeminfo'] = $this->get_system_info_data();
                break;
        }

        return $data;
    }

    /**
     * Get AI provider status data.
     *
     * @return array Provider data for template.
     */
    private function get_provider_data(): array {
        $data = [
            'heading' => get_string('debuglogs_aiprovider_heading', 'local_aiquiz_gen'),
            'activelabel' => get_string('debuglogs_activeprovider', 'local_aiquiz_gen'),
            'gatewaylabel' => get_string('debuglogs_gatewayurl', 'local_aiquiz_gen'),
            'available' => false,
            'error' => false,
        ];

        try {
            $gatewayurl = \local_aiquiz_gen\gateway_client::get_gateway_url();
            $gatewayready = \local_aiquiz_gen\gateway_client::is_ready();

            $data['available'] = true;
            $data['statusclass'] = $gatewayready ? 'success' : 'danger';
            $data['statuslabel'] = $gatewayready
                ? get_string('debuglogs_yes', 'local_aiquiz_gen')
                : get_string('debuglogs_no', 'local_aiquiz_gen');
            $data['gatewayurl'] = $gatewayurl;
            $data['showwarning'] = !$gatewayready;
            if (!$gatewayready) {
                $data['warningmessage'] = get_string('debuglogs_noprovider_warning', 'local_aiquiz_gen');
            }
        } catch (\Exception $e) {
            $data['error'] = true;
            $data['errormessage'] = get_string(
                'debuglogs_provider_error',
                'local_aiquiz_gen',
                htmlspecialchars($e->getMessage())
            );
        }

        return $data;
    }

    /**
     * Get tab navigation data.
     *
     * @return array Tabs data for template.
     */
    private function get_tabs_data(): array {
        $tabdefs = [
            'database' => get_string('debuglogs_tab_database', 'local_aiquiz_gen'),
            'file' => get_string('debuglogs_tab_file', 'local_aiquiz_gen'),
            'requests' => get_string('debuglogs_tab_requests', 'local_aiquiz_gen'),
            'system' => get_string('debuglogs_tab_system', 'local_aiquiz_gen'),
        ];

        $tabs = [];
        foreach ($tabdefs as $key => $label) {
            $url = new moodle_url('/local/aiquiz_gen/debug_logs.php', ['tab' => $key]);
            $tabs[] = [
                'key' => $key,
                'label' => $label,
                'active' => ($this->tab === $key),
                'url' => $url->out(false),
            ];
        }

        return $tabs;
    }

    /**
     * Get action buttons data.
     *
     * @return array Actions data for template.
     */
    private function get_actions_data(): array {
        $systeminfourl = new moodle_url('/local/aiquiz_gen/debug_logs.php', [
            'tab' => $this->tab,
            'action' => 'logsysteminfo',
            'sesskey' => sesskey(),
        ]);
        $testlogurl = new moodle_url('/local/aiquiz_gen/debug_logs.php', [
            'tab' => $this->tab,
            'action' => 'testlog',
            'sesskey' => sesskey(),
        ]);
        $refreshurl = new moodle_url('/local/aiquiz_gen/debug_logs.php', ['tab' => $this->tab]);

        return [
            'systeminfourl' => $systeminfourl->out(false),
            'systeminfolabel' => get_string('debuglogs_btn_logsysteminfo', 'local_aiquiz_gen'),
            'testlogurl' => $testlogurl->out(false),
            'testloglabel' => get_string('debuglogs_btn_createtestlog', 'local_aiquiz_gen'),
            'refreshurl' => $refreshurl->out(false),
            'refreshlabel' => get_string('debuglogs_btn_refresh', 'local_aiquiz_gen'),
        ];
    }

    /**
     * Get database logs tab data.
     *
     * @return array Database logs data for template.
     */
    private function get_database_logs_data(): array {
        $leveloptions = [
            ['value' => 'error', 'label' => get_string('debuglogs_filter_level_error', 'local_aiquiz_gen'),
                'selected' => ($this->level === 'error')],
            ['value' => 'warning', 'label' => get_string('debuglogs_filter_level_warning', 'local_aiquiz_gen'),
                'selected' => ($this->level === 'warning')],
            ['value' => 'info', 'label' => get_string('debuglogs_filter_level_info', 'local_aiquiz_gen'),
                'selected' => ($this->level === 'info')],
            ['value' => 'debug', 'label' => get_string('debuglogs_filter_level_debug', 'local_aiquiz_gen'),
                'selected' => ($this->level === 'debug')],
        ];

        $data = [
            'filters' => [
                'heading' => get_string('debuglogs_filters_heading', 'local_aiquiz_gen'),
                'requestidlabel' => get_string('debuglogs_filter_requestid', 'local_aiquiz_gen'),
                'requestid' => $this->requestid ?? '',
                'alllabel' => get_string('debuglogs_filter_all', 'local_aiquiz_gen'),
                'levellabel' => get_string('debuglogs_filter_level', 'local_aiquiz_gen'),
                'leveloptions' => $leveloptions,
                'limitlabel' => get_string('debuglogs_filter_limit', 'local_aiquiz_gen'),
                'limit' => $this->limit,
                'filterbtn' => get_string('debuglogs_filter_btn', 'local_aiquiz_gen'),
            ],
            'headers' => [
                'time' => get_string('debuglogs_table_time', 'local_aiquiz_gen'),
                'level' => get_string('debuglogs_table_level', 'local_aiquiz_gen'),
                'request' => get_string('debuglogs_table_request', 'local_aiquiz_gen'),
                'user' => get_string('debuglogs_table_user', 'local_aiquiz_gen'),
                'component' => get_string('debuglogs_table_component', 'local_aiquiz_gen'),
                'message' => get_string('debuglogs_table_message', 'local_aiquiz_gen'),
                'details' => get_string('debuglogs_table_details', 'local_aiquiz_gen'),
            ],
            'haslogs' => false,
            'logs' => [],
            'nologsmessage' => get_string('debuglogs_nologs', 'local_aiquiz_gen'),
        ];

        $logs = debug_logger::getrecentdatabaselogs($this->limit, $this->requestid, $this->level ?: null);

        if (!empty($logs)) {
            $data['haslogs'] = true;
            foreach ($logs as $log) {
                $logitem = [
                    'id' => $log->id,
                    'time' => userdate($log->timecreated, '%Y-%m-%d %H:%M:%S'),
                    'levelclass' => self::get_level_class($log->status),
                    'levelupper' => strtoupper($log->status),
                    'requestid' => $log->requestid ?: '-',
                    'userid' => $log->userid,
                    'component' => htmlspecialchars($log->component ?? '-'),
                    'message' => htmlspecialchars(substr($log->error_message ?? '', 0, 100)),
                    'hasdetails' => false,
                    'detailsbtn' => get_string('debuglogs_btn_viewdetails', 'local_aiquiz_gen'),
                ];

                if (!empty($log->details)) {
                    $details = json_decode($log->details, true);
                    if ($details) {
                        $logitem['hasdetails'] = true;
                        $logitem['detailsformatted'] = htmlspecialchars(
                            json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );
                    }
                }

                $data['logs'][] = $logitem;
            }
        }

        return $data;
    }

    /**
     * Get file logs tab data.
     *
     * @return array File logs data for template.
     */
    private function get_file_logs_data(): array {
        $logfile = debug_logger::getlogfilepath();

        $clearurl = new moodle_url('/local/aiquiz_gen/debug_logs.php', [
            'tab' => 'file',
            'action' => 'clearfile',
            'sesskey' => sesskey(),
        ]);

        $data = [
            'heading' => get_string('debuglogs_logfile_heading', 'local_aiquiz_gen'),
            'clearurl' => $clearurl->out(false),
            'clearconfirm' => get_string('debuglogs_clearfile_confirm', 'local_aiquiz_gen'),
            'clearbtn' => get_string('debuglogs_btn_clearlogfile', 'local_aiquiz_gen'),
            'pathlabel' => get_string('debuglogs_logfile_path', 'local_aiquiz_gen'),
            'sizelabel' => get_string('debuglogs_logfile_size', 'local_aiquiz_gen'),
            'haslogfile' => false,
            'fileexists' => false,
            'hasentries' => false,
            'emptymessage' => get_string('debuglogs_logfile_empty', 'local_aiquiz_gen'),
            'notexistmessage' => get_string('debuglogs_logfile_notexist', 'local_aiquiz_gen'),
            'notfoundmessage' => get_string('debuglogs_logfile_notfound', 'local_aiquiz_gen'),
        ];

        if ($logfile) {
            $data['haslogfile'] = true;
            $data['logfilepath'] = $logfile;

            if (file_exists($logfile)) {
                $data['fileexists'] = true;
                $data['filesize'] = self::format_bytes(filesize($logfile));

                $entries = debug_logger::getrecentfilelogs($this->limit);

                if (!empty($entries)) {
                    $data['hasentries'] = true;
                    $data['showingcount'] = get_string(
                        'debuglogs_logfile_showing',
                        'local_aiquiz_gen',
                        count($entries)
                    );

                    // Format entries with color coding.
                    $formatted = '';
                    foreach (array_reverse($entries) as $entry) {
                        $entry = htmlspecialchars(trim($entry));
                        $entry = preg_replace(
                            '/\[ERROR\]/',
                            '<span class="aiquiz-log-error">[ERROR]</span>',
                            $entry
                        );
                        $entry = preg_replace(
                            '/\[WARNING\]/',
                            '<span class="aiquiz-log-warning">[WARNING]</span>',
                            $entry
                        );
                        $entry = preg_replace(
                            '/\[CRITICAL\]/',
                            '<span class="aiquiz-log-critical">[CRITICAL]</span>',
                            $entry
                        );
                        $entry = preg_replace(
                            '/\[INFO\]/',
                            '<span class="aiquiz-log-info">[INFO]</span>',
                            $entry
                        );
                        $entry = preg_replace(
                            '/\[DEBUG\]/',
                            '<span class="aiquiz-log-debug">[DEBUG]</span>',
                            $entry
                        );
                        $formatted .= $entry . "\n" . str_repeat('-', 80) . "\n";
                    }
                    $data['formattedentries'] = $formatted;
                }
            }
        }

        return $data;
    }

    /**
     * Get recent requests tab data.
     *
     * @return array Requests data for template.
     */
    private function get_requests_data(): array {
        global $DB;

        $data = [
            'hasrequests' => false,
            'nofoundmessage' => get_string('debuglogs_requests_nofound', 'local_aiquiz_gen'),
            'headers' => [
                'id' => get_string('debuglogs_requests_table_id', 'local_aiquiz_gen'),
                'course' => get_string('debuglogs_requests_table_course', 'local_aiquiz_gen'),
                'user' => get_string('debuglogs_requests_table_user', 'local_aiquiz_gen'),
                'status' => get_string('debuglogs_requests_table_status', 'local_aiquiz_gen'),
                'questions' => get_string('debuglogs_requests_table_questions', 'local_aiquiz_gen'),
                'tokens' => get_string('debuglogs_requests_table_tokens', 'local_aiquiz_gen'),
                'created' => get_string('debuglogs_requests_table_created', 'local_aiquiz_gen'),
                'error' => get_string('debuglogs_requests_table_error', 'local_aiquiz_gen'),
                'actions' => get_string('debuglogs_requests_table_actions', 'local_aiquiz_gen'),
            ],
            'items' => [],
        ];

        $requests = $DB->get_records('local_aiquiz_gen_requests', [], 'timecreated DESC', '*', 0, $this->limit);

        if (!empty($requests)) {
            $data['hasrequests'] = true;
            $viewlogsbtn = get_string('debuglogs_requests_btn_viewlogs', 'local_aiquiz_gen');

            foreach ($requests as $req) {
                $logsurl = new moodle_url('/local/aiquiz_gen/debug_logs.php', [
                    'tab' => 'database',
                    'requestid' => $req->id,
                ]);

                $haserror = !empty($req->error_message);
                $hasprogress = !$haserror && !empty($req->progress_message);

                $data['items'][] = [
                    'id' => $req->id,
                    'courseid' => $req->courseid,
                    'userid' => $req->userid,
                    'statusclass' => self::get_status_class($req->status),
                    'statusupper' => strtoupper($req->status),
                    'questionscount' => $req->questions_generated ?? $req->total_questions ?? 0,
                    'tokenscount' => $req->total_tokens ?? 0,
                    'time' => userdate($req->timecreated, '%Y-%m-%d %H:%M:%S'),
                    'haserror' => $haserror,
                    'errorfull' => $haserror ? htmlspecialchars($req->error_message) : '',
                    'errortruncated' => $haserror ? htmlspecialchars(substr($req->error_message, 0, 50)) : '',
                    'hasprogress' => $hasprogress,
                    'progresstruncated' => $hasprogress ?
                        htmlspecialchars(substr($req->progress_message, 0, 50)) : '',
                    'noerror' => !$haserror && !$hasprogress,
                    'logsurl' => $logsurl->out(false),
                    'viewlogsbtn' => $viewlogsbtn,
                ];
            }
        }

        return $data;
    }

    /**
     * Get system info tab data.
     *
     * @return array System info data for template.
     */
    private function get_system_info_data(): array {
        global $CFG, $DB;

        $unknownstr = get_string('debuglogs_unknown', 'local_aiquiz_gen');

        // PHP Configuration.
        $errorlog = ini_get('error_log') ?: get_string('debuglogs_system_notset', 'local_aiquiz_gen');
        $php = [
            'heading' => get_string('debuglogs_system_phpconfig', 'local_aiquiz_gen'),
            'items' => [
                ['label' => get_string('debuglogs_system_phpversion', 'local_aiquiz_gen'), 'value' => PHP_VERSION],
                ['label' => get_string('debuglogs_system_memorylimit', 'local_aiquiz_gen'),
                    'value' => ini_get('memory_limit')],
                ['label' => get_string('debuglogs_system_maxexectime', 'local_aiquiz_gen'),
                    'value' => ini_get('max_execution_time') . 's'],
                ['label' => get_string('debuglogs_system_postmaxsize', 'local_aiquiz_gen'),
                    'value' => ini_get('post_max_size')],
                ['label' => get_string('debuglogs_system_uploadmaxfilesize', 'local_aiquiz_gen'),
                    'value' => ini_get('upload_max_filesize')],
                ['label' => get_string('debuglogs_system_errorlog', 'local_aiquiz_gen'), 'value' => $errorlog],
            ],
        ];

        // PHP Extensions.
        $extnames = ['curl', 'json', 'zip', 'simplexml', 'openssl', 'zlib', 'fileinfo'];
        $extensions = [
            'heading' => get_string('debuglogs_system_extensions', 'local_aiquiz_gen'),
            'items' => [],
        ];
        foreach ($extnames as $ext) {
            $loaded = extension_loaded($ext);
            $extensions['items'][] = [
                'name' => $ext,
                'statusclass' => $loaded ? 'success' : 'danger',
                'statuslabel' => $loaded
                    ? get_string('debuglogs_system_ext_loaded', 'local_aiquiz_gen')
                    : get_string('debuglogs_system_ext_missing', 'local_aiquiz_gen'),
            ];
        }

        // Moodle Configuration.
        $moodle = [
            'heading' => get_string('debuglogs_system_moodleconfig', 'local_aiquiz_gen'),
            'items' => [
                ['label' => get_string('debuglogs_system_moodleversion', 'local_aiquiz_gen'),
                    'value' => $CFG->release ?? $unknownstr],
                ['label' => get_string('debuglogs_system_moodlebuild', 'local_aiquiz_gen'),
                    'value' => $CFG->version ?? $unknownstr],
                ['label' => get_string('debuglogs_system_wwwroot', 'local_aiquiz_gen'),
                    'value' => $CFG->wwwroot],
                ['label' => get_string('debuglogs_system_dataroot', 'local_aiquiz_gen'),
                    'value' => $CFG->dataroot],
                ['label' => get_string('debuglogs_system_debugmode', 'local_aiquiz_gen'),
                    'value' => (string) ($CFG->debug ?? 0)],
            ],
        ];

        // Plugin Statistics.
        $plugin = [
            'heading' => get_string('debuglogs_system_pluginstats', 'local_aiquiz_gen'),
            'error' => false,
            'items' => [],
        ];

        try {
            $totalrequests = $DB->count_records('local_aiquiz_gen_requests');
            $failedrequests = $DB->count_records('local_aiquiz_gen_requests', ['status' => 'failed']);
            $completedrequests = $DB->count_records('local_aiquiz_gen_requests', ['status' => 'completed']);
            $totalquestions = $DB->count_records('local_aiquiz_gen_questions');
            $totallogs = $DB->count_records('local_aiquiz_gen_logs');

            $plugin['items'] = [
                ['label' => get_string('debuglogs_system_totalrequests', 'local_aiquiz_gen'),
                    'value' => (string) $totalrequests, 'hastag' => false],
                ['label' => get_string('debuglogs_system_completedrequests', 'local_aiquiz_gen'),
                    'value' => (string) $completedrequests, 'hastag' => true, 'tagclass' => 'success'],
                ['label' => get_string('debuglogs_system_failedrequests', 'local_aiquiz_gen'),
                    'value' => (string) $failedrequests, 'hastag' => true, 'tagclass' => 'danger'],
                ['label' => get_string('debuglogs_system_totalquestions', 'local_aiquiz_gen'),
                    'value' => (string) $totalquestions, 'hastag' => false],
                ['label' => get_string('debuglogs_system_totallogs', 'local_aiquiz_gen'),
                    'value' => (string) $totallogs, 'hastag' => false],
            ];
        } catch (\Exception $e) {
            $plugin['error'] = true;
            $plugin['errormessage'] = get_string(
                'debuglogs_system_stats_error',
                'local_aiquiz_gen',
                htmlspecialchars($e->getMessage())
            );
        }

        // System Tools.
        $tools = [
            'heading' => get_string('debuglogs_system_tools', 'local_aiquiz_gen'),
            'items' => [],
        ];

        $pathdirs = explode(PATH_SEPARATOR, getenv('PATH') ?: '');

        // Check pdftotext.
        $pdftotextfound = false;
        foreach ($pathdirs as $dir) {
            if (
                is_executable($dir . DIRECTORY_SEPARATOR . 'pdftotext') ||
                is_executable($dir . DIRECTORY_SEPARATOR . 'pdftotext.exe')
            ) {
                $pdftotextfound = true;
                break;
            }
        }
        $tools['items'][] = [
            'label' => get_string('debuglogs_system_tool_pdftotext', 'local_aiquiz_gen'),
            'statusclass' => $pdftotextfound ? 'success' : 'warning',
            'statuslabel' => $pdftotextfound
                ? get_string('debuglogs_system_tool_available', 'local_aiquiz_gen')
                : get_string('debuglogs_system_tool_notfound', 'local_aiquiz_gen'),
        ];

        // Check ghostscript.
        $gsfound = false;
        $gsnames = ['gs', 'gs.exe', 'gswin64c.exe', 'gswin32c.exe'];
        foreach ($pathdirs as $dir) {
            foreach ($gsnames as $gsname) {
                if (is_executable($dir . DIRECTORY_SEPARATOR . $gsname)) {
                    $gsfound = true;
                    break 2;
                }
            }
        }
        $tools['items'][] = [
            'label' => get_string('debuglogs_system_tool_ghostscript', 'local_aiquiz_gen'),
            'statusclass' => $gsfound ? 'success' : 'warning',
            'statuslabel' => $gsfound
                ? get_string('debuglogs_system_tool_available', 'local_aiquiz_gen')
                : get_string('debuglogs_system_tool_notfound', 'local_aiquiz_gen'),
        ];

        return [
            'php' => $php,
            'extensions' => $extensions,
            'moodle' => $moodle,
            'plugin' => $plugin,
            'tools' => $tools,
        ];
    }

    /**
     * Get Bulma tag color class for log level.
     *
     * @param string $level The log level.
     * @return string The CSS class name.
     */
    private static function get_level_class(string $level): string {
        switch (strtolower($level)) {
            case 'critical':
            case 'error':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'info':
                return 'info';
            case 'debug':
                return 'dark';
            default:
                return 'light';
        }
    }

    /**
     * Get Bulma tag color class for request status.
     *
     * @param string $status The request status.
     * @return string The CSS class name.
     */
    private static function get_status_class(string $status): string {
        switch (strtolower($status)) {
            case 'completed':
                return 'success';
            case 'failed':
                return 'danger';
            case 'processing':
                return 'warning';
            case 'pending':
                return 'info';
            default:
                return 'light';
        }
    }

    /**
     * Format bytes to human readable string.
     *
     * @param int $bytes Number of bytes.
     * @return string Human-readable file size.
     */
    private static function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
