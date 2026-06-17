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
 * Seed an AI provider config from environment variables.
 *
 * Usage (from the host):
 *     docker compose exec moodle php /var/www/html/local/aiquiz_gen/bin/seed_provider.php
 *
 * Reads:
 *     MOODLE_AI_PROVIDER      gemini | openai | ollama (default: gemini)
 *     MOODLE_AI_GEMINI_APIKEY
 *     MOODLE_AI_GEMINI_MODEL  default: gemini-2.5-flash
 *     MOODLE_AI_OPENAI_APIKEY
 *     MOODLE_AI_OPENAI_MODEL  default: gpt-4o-mini
 *     MOODLE_AI_OLLAMA_BASEURL default: http://host.docker.internal:11434
 *     MOODLE_AI_OLLAMA_MODEL  default: llama3.1
 *
 * Idempotent: existing providers (matched by name) are updated in place,
 * not duplicated. Re-run safely.
 *
 * @package    local_aiquiz_gen
 * @copyright  2026 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');

require_once($CFG->dirroot . '/local/aiquiz_gen/classes/ai_client.php');

$provider = strtolower(trim(getenv('MOODLE_AI_PROVIDER') ?: 'gemini'));

$providers = [
    'gemini' => [
        'class' => 'aiprovider_gemini\\provider',
        'name' => 'gemini',
        'config' => static function (): array {
            $key = trim((string)getenv('MOODLE_AI_GEMINI_APIKEY'));
            if ($key === '') {
                throw new \RuntimeException('MOODLE_AI_GEMINI_APIKEY is not set.');
            }
            return ['apikey' => $key];
        },
        'actions' => static function (): array {
            $model = trim((string)(getenv('MOODLE_AI_GEMINI_MODEL') ?: 'gemini-2.5-flash'));
            return [
                'core_ai\\aiactions\\generate_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
                        'systeminstruction' => '',
                    ],
                ],
                'core_ai\\aiactions\\summarise_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
                        'systeminstruction' => '',
                    ],
                ],
            ];
        },
    ],
    'openai' => [
        'class' => 'aiprovider_openai\\provider',
        'name' => 'openai',
        'config' => static function (): array {
            $key = trim((string)getenv('MOODLE_AI_OPENAI_APIKEY'));
            if ($key === '') {
                throw new \RuntimeException('MOODLE_AI_OPENAI_APIKEY is not set.');
            }
            return ['apikey' => $key];
        },
        'actions' => static function (): array {
            $model = trim((string)(getenv('MOODLE_AI_OPENAI_MODEL') ?: 'gpt-4o-mini'));
            return [
                'core_ai\\aiactions\\generate_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => 'https://api.openai.com/v1',
                        'systeminstruction' => '',
                    ],
                ],
                'core_ai\\aiactions\\summarise_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => 'https://api.openai.com/v1',
                        'systeminstruction' => '',
                    ],
                ],
            ];
        },
    ],
    'ollama' => [
        'class' => 'aiprovider_ollama\\provider',
        'name' => 'ollama',
        'config' => static function (): array {
            $base = trim((string)(getenv('MOODLE_AI_OLLAMA_BASEURL') ?: 'http://host.docker.internal:11434'));
            return ['apikey' => 'ollama', 'baseurl' => $base];
        },
        'actions' => static function (): array {
            $model = trim((string)(getenv('MOODLE_AI_OLLAMA_MODEL') ?: 'llama3.1'));
            $base = trim((string)(getenv('MOODLE_AI_OLLAMA_BASEURL') ?: 'http://host.docker.internal:11434'));
            return [
                'core_ai\\aiactions\\generate_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => $base,
                        'systeminstruction' => '',
                    ],
                ],
                'core_ai\\aiactions\\summarise_text' => [
                    'enabled' => true,
                    'settings' => [
                        'model' => $model,
                        'endpoint' => $base,
                        'systeminstruction' => '',
                    ],
                ],
            ];
        },
    ],
];

if (!isset($providers[$provider])) {
    cli_error("Unknown provider '{$provider}'. Supported: " . implode(', ', array_keys($providers)));
}

$spec = $providers[$provider];

try {
    $config = ($spec['config'])();
    $actionconfig = ($spec['actions'])();
} catch (\RuntimeException $e) {
    cli_error($e->getMessage());
}

$configjson = json_encode($config, JSON_UNESCAPED_SLASHES);
$actionjson = json_encode($actionconfig, JSON_UNESCAPED_SLASHES);

$existing = $DB->get_record('ai_providers', ['provider' => $spec['class']]);

if ($existing) {
    $existing->config = $configjson;
    $existing->actionconfig = $actionjson;
    $existing->enabled = 1;
    $existing->name = $spec['name'];
    $DB->update_record('ai_providers', $existing);
    cli_writeln("Updated existing provider id={$existing->id} ({$spec['class']}).");
} else {
    $record = new \stdClass();
    $record->name = $spec['name'];
    $record->provider = $spec['class'];
    $record->enabled = 1;
    $record->config = $configjson;
    $record->actionconfig = $actionjson;
    $id = $DB->insert_record('ai_providers', $record);
    cli_writeln("Inserted new provider id={$id} ({$spec['class']}).");
}

// Verify the plugin can now see a configured provider.
if (\local_aiquiz_gen\ai_client::is_ready()) {
    $info = \local_aiquiz_gen\ai_client::get_provider_info();
    cli_writeln("ai_client::is_ready() = true. Active provider: {$info}");
} else {
    cli_writeln('WARNING: provider was saved but ai_client::is_ready() still returns false.');
    cli_writeln('Check Site administration → AI → Providers, then Notifications to ensure the aiprovider_' . $provider . ' plugin is installed.');
}
