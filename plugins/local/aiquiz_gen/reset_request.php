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
 * Browser helper to reset a quizgen request back to pending.
 *
 * @package    local_aiquiz_gen
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$requestid = optional_param('requestid', 0, PARAM_INT);
$doreset   = optional_param('doreset', 0, PARAM_BOOL);

require_login();
$context = context_system::instance();
// Allow quizgen report viewers or site admins.
if (!has_capability('local/aiquiz_gen:viewreports', $context) && !has_capability('moodle/site:config', $context)) {
    require_capability('local/aiquiz_gen:viewreports', $context);
}

$PAGE->set_url('/local/aiquiz_gen/reset_request.php', ['requestid' => $requestid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('reset_request_title', 'local_aiquiz_gen'));
$PAGE->set_heading(get_string('reset_request_title', 'local_aiquiz_gen'));
$PAGE->requires->css('/local/aiquiz_gen/bulma.css');
$PAGE->requires->css('/local/aiquiz_gen/styles-bulma.css');

global $DB, $OUTPUT;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reset_request_title', 'local_aiquiz_gen'), 2);
echo html_writer::start_div('aiquiz-gen-wrapper local-aiquiz-gen');

echo html_writer::div(
    get_string('reset_request_info', 'local_aiquiz_gen'),
    'notification is-info is-light'
);

if ($doreset && confirm_sesskey() && $requestid) {
    $req = $DB->get_record('local_aiquiz_gen_requests', ['id' => $requestid], '*', IGNORE_MISSING);
    if (!$req) {
        echo html_writer::div(get_string('reset_request_notfound', 'local_aiquiz_gen'), 'notification is-danger is-light');
    } else {
        $update = new stdClass();
        $update->id = $requestid;
        $update->status = 'pending';
        $update->error_message = null;
        $update->timemodified = time();
        $update->timecompleted = null;
        $DB->update_record('local_aiquiz_gen_requests', $update);
        $msg = get_string('reset_request_success', 'local_aiquiz_gen', $requestid);
        echo html_writer::div($msg, 'notification is-success is-light');
    }
}

// Show form.
$formurl = new moodle_url('/local/aiquiz_gen/reset_request.php');
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'doreset', 'value' => 1]);
echo html_writer::start_div('field');
$label = get_string('reset_request_id_label', 'local_aiquiz_gen');
echo html_writer::tag('label', $label, ['for' => 'requestid', 'class' => 'label']);
echo html_writer::start_div('control');
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'requestid',
    'id' => 'requestid',
    'value' => $requestid ?: '',
    'required' => 'required',
    'class' => 'input',
]);
echo html_writer::end_div();
echo html_writer::end_div();
$btntext = get_string('reset_request_submit', 'local_aiquiz_gen');
echo html_writer::tag('button', $btntext, ['type' => 'submit', 'class' => 'button is-primary']);
echo html_writer::end_tag('form');

echo html_writer::end_div();
echo $OUTPUT->footer();
