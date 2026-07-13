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
 * Provider-agnostic AI client built on Moodle's core_ai framework.
 *
 * Replaces the proprietary Human Logic gateway that the original plugin shipped
 * with. Any aiprovider_* plugin that supports the generate_text action will
 * work here (openai, gemini, ollama, deepseek, awsbedrock, azureai all ship in
 * Moodle 5.2 core).
 *
 * Public API is intentionally a drop-in replacement for gateway_client so the
 * existing call sites in question_generator.php and topic_analyzer.php do not
 * need to change.
 *
 * @package    local_aiquiz_gen
 * @copyright  2026 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiquiz_gen;

/**
 * Thin wrapper over \core_ai\manager that builds the prompts the original
 * plugin assumed a remote gateway would build server-side.
 */
class ai_client {

    /**
     * Whether at least one enabled, configured AI provider is available.
     *
     * Mirrors ai_client::is_ready() so the existing readiness checks
     * (in question_generator.php, topic_analyzer.php, wizard_helper.php)
     * continue to work unchanged.
     *
     * @return bool
     */
    public static function is_ready(): bool {
        global $DB;
        $manager = new \core_ai\manager($DB);
        $providers = $manager->get_providers_for_actions(
            [\core_ai\aiactions\generate_text::class],
            enabledonly: true,
        );
        return !empty($providers[\core_ai\aiactions\generate_text::class]);
    }

    /**
     * Human-readable description of the active provider(s) for diagnostics
     * pages (debug logs, health check). Replaces ai_client::get_provider_info().
     *
     * @return string
     */
    public static function get_provider_info(): string {
        global $DB;
        $manager = new \core_ai\manager($DB);
        $providers = $manager->get_providers_for_actions(
            [\core_ai\aiactions\generate_text::class],
            enabledonly: true,
        );
        $names = [];
        foreach ($providers[\core_ai\aiactions\generate_text::class] ?? [] as $p) {
            $names[] = $p->get_name();
        }
        return $names ? implode(', ', $names) : get_string('error:noprovider', 'local_aiquiz_gen');
    }

    /**
     * Generate one or more quiz questions on a given topic.
     *
     * Drop-in replacement for ai_client::generate_questions(). The
     * existing parser in question_generator.php expects each item of the
     * returned 'questions' array to already be a structured object, so this
     * method does the LLM call AND the JSON-to-PHP conversion here.
     *
     * @param array $payload Expects: topic_title, topic_content, question_types,
     *                       difficulty_distribution, blooms_distribution,
     *                       num_questions, existing_questions, is_regeneration,
     *                       old_question_text
     * @param string $quality fast|balanced|best — controls prompt verbosity only,
     *                        model selection is the admin's responsibility
     * @return array ['questions' => array<object>, 'tokens' => object, 'provider' => string]
     * @throws \moodle_exception If no provider is configured or the call fails
     */
    public static function generate_questions(array $payload, string $quality = 'balanced'): array {
        if (!self::is_ready()) {
            throw new \moodle_exception(
                'error:noaiprovider',
                'local_aiquiz_gen',
                '',
                get_string('error:noaiprovider_detail', 'local_aiquiz_gen'),
            );
        }

        $types = $payload['question_types'] ?? ['multichoice'];
        $prompt = self::build_questions_prompt($payload, $quality);
        $result = self::call_generate_text($prompt);

        $questions = self::parse_questions_response($result['content'], $types);

        return [
            'questions' => $questions,
            'tokens' => $result['tokens'],
            'provider' => $result['provider'],
        ];
    }

    /**
     * Extract a list of assessable topics from raw course content.
     *
     * Drop-in replacement for ai_client::analyze_topics(). Returns
     * topics as a plain array (the existing topic_analyzer.php only reads
     * the 'title' field of each topic; extra fields are ignored).
     *
     * @param array $payload Expects: content, courseid
     * @param string $quality fast|balanced|best
     * @return array ['topics' => array, 'tokens' => object, 'provider' => string]
     * @throws \moodle_exception If no provider is configured or the call fails
     */
    public static function analyze_topics(array $payload, string $quality = 'balanced'): array {
        if (!self::is_ready()) {
            throw new \moodle_exception(
                'error:noaiprovider',
                'local_aiquiz_gen',
                '',
                get_string('error:noaiprovider_detail', 'local_aiquiz_gen'),
            );
        }

        $prompt = self::build_topics_prompt($payload, $quality);
        $result = self::call_generate_text($prompt);

        $topics = self::parse_topics_response($result['content']);

        return [
            'topics' => $topics,
            'tokens' => $result['tokens'],
            'provider' => $result['provider'],
        ];
    }

    /**
     * Rewrite a single question (e.g. to fix wording, change difficulty, etc.).
     *
     * Declared for API parity with gateway_client. Not currently called by
     * any other class in this plugin.
     *
     * @param array $payload Expects: question (the question object to refine),
     *                       instructions (optional rewrite direction)
     * @param string $quality fast|balanced|best
     * @return array ['question' => object, 'tokens' => object, 'provider' => string]
     * @throws \moodle_exception
     */
    public static function refine_question(array $payload, string $quality = 'balanced'): array {
        if (!self::is_ready()) {
            throw new \moodle_exception('error:noaiprovider', 'local_aiquiz_gen');
        }

        $prompt = self::build_refine_prompt($payload, $quality);
        $result = self::call_generate_text($prompt);
        $questions = self::parse_questions_response(
            $result['content'],
            [$payload['question']->questiontype ?? 'multichoice'],
        );

        return [
            'question' => $questions[0] ?? null,
            'tokens' => $result['tokens'],
            'provider' => $result['provider'],
        ];
    }

    /**
     * Generate additional distractor answers for an existing multichoice question.
     *
     * Declared for API parity with gateway_client. Not currently called by
     * any other class in this plugin.
     *
     * @param array $payload Expects: question, num_distractors
     * @param string $quality fast|balanced|best
     * @return array ['distractors' => array, 'tokens' => object, 'provider' => string]
     * @throws \moodle_exception
     */
    public static function generate_distractors(array $payload, string $quality = 'balanced'): array {
        if (!self::is_ready()) {
            throw new \moodle_exception('error:noaiprovider', 'local_aiquiz_gen');
        }

        $prompt = self::build_distractors_prompt($payload, $quality);
        $result = self::call_generate_text($prompt);
        $distractors = self::parse_distractors_response($result['content']);

        return [
            'distractors' => $distractors,
            'tokens' => $result['tokens'],
            'provider' => $result['provider'],
        ];
    }

    // ─── Provider invocation ────────────────────────────────────────────

    /**
     * Invoke the configured AI provider's generate_text action.
     *
     * @param string $prompt The fully-built prompt text
     * @return array ['content' => string, 'tokens' => object, 'provider' => string]
     * @throws \moodle_exception On provider error
     */
    private static function call_generate_text(string $prompt): array {
        global $DB, $USER;

        $contextid = self::resolve_context_id();
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id ?? 0,
            prompttext: $prompt,
        );

        $manager = new \core_ai\manager($DB);

        \local_aiquiz_gen\debug_logger::debug('AI call', [
            'prompt_length' => strlen($prompt),
            'context_id' => $contextid,
        ]);

        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            $errorcode = $response->get_errorcode();
            $errormessage = $response->get_errormessage();
            \local_aiquiz_gen\debug_logger::error('AI call failed', [
                'errorcode' => $errorcode,
                'error' => $response->get_error(),
                'errormessage' => $errormessage,
            ]);
            throw new \moodle_exception(
                'error:aigeneration',
                'local_aiquiz_gen',
                '',
                null,
                $errormessage ?: $response->get_error(),
            );
        }

        $data = $response->get_response_data();
        $content = $data['generatedcontent'] ?? '';
        $tokens = (object) [
            'prompt' => $data['prompttokens'] ?? 0,
            'completion' => $data['completiontokens'] ?? 0,
            'total' => ($data['prompttokens'] ?? 0) + ($data['completiontokens'] ?? 0),
        ];

        // Provider name is exposed in the response model field by the core framework.
        $provider = $data['model'] ?? $response->get_model_used() ?? 'unknown';

        return [
            'content' => $content,
            'tokens' => $tokens,
            'provider' => $provider,
        ];
    }

    /**
     * Pick the most specific context available so the AI policy / rate limiter
     * scope correctly. Prefers a course context if the plugin's UI is active.
     *
     * @return int A valid Moodle context id
     */
    private static function resolve_context_id(): int {
        global $PAGE;

        if (!empty($PAGE->context) && $PAGE->context instanceof \context) {
            return $PAGE->context->id;
        }
        return \context_system::instance()->id;
    }

    // ─── Prompt builders (XML-tagged) ───────────────────────────────────

    /**
     * Build the prompt that produces a JSON array of questions matching
     * the shape parse_questions_response() expects.
     */
    private static function build_questions_prompt(array $payload, string $quality): string {
        $types = $payload['question_types'] ?? ['multichoice'];
        $difficultydist = $payload['difficulty_distribution'] ?? ['easy' => 20, 'medium' => 60, 'hard' => 20];
        $bloomsdist = $payload['blooms_distribution'] ?? [
            'remember' => 20, 'understand' => 25, 'apply' => 25,
            'analyze' => 15, 'evaluate' => 10, 'create' => 5,
        ];
        $existing = $payload['existing_questions'] ?? [];
        $num = (int)($payload['num_questions'] ?? count($types));
        $isregen = !empty($payload['is_regeneration']);
        $oldq = $payload['old_question_text'] ?? '';

        $typescsv = implode(', ', $types);
        $existingblock = '';
        if (!empty($existing)) {
            $items = '';
            foreach (array_slice($existing, 0, 30) as $q) {
                $items .= "\n    <existing_question><![CDATA[" . self::clean_for_cdata($q) . "]]></existing_question>";
            }
            $existingblock = "\n  <existing_questions_to_avoid>{$items}\n  </existing_questions_to_avoid>";
        }

        $regenblock = '';
        if ($isregen && $oldq !== '') {
            $regenblock = "\n  <regeneration>\n    <previous_question><![CDATA[" .
                self::clean_for_cdata($oldq) . "]]></previous_question>\n    <instruction>Generate a question on the same topic that is clearly different from the previous one.</instruction>\n  </regeneration>";
        }

        $extra = $quality === 'best'
            ? "\n  <quality_mode>best</quality_mode>\n  <instruction>Be thorough: include rich feedback on every answer, justify the bloom level in ai_reasoning, and prefer nuanced distractors that target common misconceptions.</instruction>"
            : ($quality === 'fast'
                ? "\n  <quality_mode>fast</quality_mode>\n  <instruction>Be concise: short stems, short feedback, minimal reasoning.</instruction>"
                : "\n  <quality_mode>balanced</quality_mode>");

        // Pre-compute CDATA-wrapped values (PHP heredoc cannot call self:: inside {}).
        $titletag = '<![CDATA[' . self::clean_for_cdata((string)($payload['topic_title'] ?? '')) . ']]>';
        $contenttag = '<![CDATA[' . self::clean_for_cdata((string)($payload['topic_content'] ?? '')) . ']]>';

        // Language block (overrides default "<rule type=language>" when present).
        $langblock = self::build_language_block($payload['language'] ?? null);
        $languagerule = $langblock !== ''
            ? ''  // Explicit language overrides the generic rule.
            : "\n  <rule type=\"language\">Use the same language as the topic content. Do not translate.</rule>";

        return <<<PROMPT
<role>
You are an expert educational assessment designer. You create rigorous, fair,
and pedagogically sound quiz questions grounded in the provided course content.
You follow Bloom's taxonomy and write distractors that target common misconceptions
rather than obviously absurd answers.
</role>

<task>
Generate exactly {$num} quiz question(s) about the topic described below.
Each question must be in one of the requested types, drawn from the topic
content, and must follow the distribution constraints.
</task>

<topic>
  <title>{$titletag}</title>
  <content>{$contenttag}</content>
</topic>{$existingblock}{$regenblock}

<requirements>
  <question_types>{$typescsv}</question_types>
  <num_questions>{$num}</num_questions>
  <difficulty_distribution>
    <easy>{$difficultydist['easy']}%</easy>
    <medium>{$difficultydist['medium']}%</medium>
    <hard>{$difficultydist['hard']}%</hard>
  </difficulty_distribution>
  <blooms_distribution>
    <remember>{$bloomsdist['remember']}%</remember>
    <understand>{$bloomsdist['understand']}%</understand>
    <apply>{$bloomsdist['apply']}%</apply>
    <analyze>{$bloomsdist['analyze']}%</analyze>
    <evaluate>{$bloomsdist['evaluate']}%</evaluate>
    <create>{$bloomsdist['create']}%</create>
  </blooms_distribution>{$extra}
</requirements>{$langblock}

<constraints>
  <rule type="content_fidelity">All questions, answers, and feedback must be derivable from the topic content above. Do not invent facts.</rule>
  <rule type="multichoice_options">Every multichoice question must have exactly 4 answer options.</rule>
  <rule type="single_correct">In each multichoice question, exactly one answer has fraction=1.0; the other three have fraction=0.0.</rule>
  <rule type="truefalse">Every truefalse question has exactly 2 options: one with fraction=1.0, one with fraction=0.0. The answer text must be "True" or "False" (or their localized equivalents in the output language).</rule>
  <rule type="shortanswer">For shortanswer, include a single answer with fraction=1.0 containing the expected response. Optionally add a few alternative acceptable answers with fraction=0.5 or so.</rule>
  <rule type="essay">For essay, the answers array may be empty. Provide detailed generalfeedback describing what a good answer would include.</rule>
  <rule type="matching">For matching, omit the answers array and provide a "subquestions" array of {"questiontext": "...", "answertext": "..."} pairs (at least 3 pairs).</rule>
  <rule type="distractors">Distractors must be plausible to a learner who has not mastered the topic, but unambiguously wrong to one who has.</rule>
  <rule type="feedback">Every answer must include a short feedback string explaining why it is right or wrong.</rule>
  <rule type="general_feedback">Each question must include generalfeedback that reinforces the underlying concept.</rule>
  <rule type="deduplication">Do not repeat or closely paraphrase any question in <existing_questions_to_avoid>.</rule>{$languagerule}
</constraints>

<output_format>
Return ONLY a valid JSON array. No prose, no markdown fences, no commentary.
The array must contain exactly {$num} element(s), each matching this schema:

[
  {
    "questiontext": "string (the question stem)",
    "generalfeedback": "string (conceptual explanation of the correct answer)",
    "difficulty": "easy|medium|hard",
    "blooms_level": "remember|understand|apply|analyze|evaluate|create",
    "ai_reasoning": "string (1-2 sentences justifying the chosen difficulty and bloom level)",
    "answers": [
      {"answertext": "string", "fraction": 1.0, "feedback": "string"},
      {"answertext": "string", "fraction": 0.0, "feedback": "string"},
      ...
    ]
  }
]
</output_format>
PROMPT;
    }

    /**
     * Build the prompt that produces a JSON object with a "topics" array.
     */
    private static function build_topics_prompt(array $payload, string $quality): string {
        $content = $payload['content'] ?? '';
        $extra = $quality === 'best'
            ? "\n  <quality_mode>best</quality_mode>\n  <instruction>Be exhaustive: identify every distinct concept, technique, or fact that could be assessed. Aim for 8-15 topics.</instruction>"
            : "\n  <quality_mode>{$quality}</quality_mode>\n  <instruction>Aim for 5-10 high-quality, non-overlapping topics.</instruction>";

        $contenttag = '<![CDATA[' . self::clean_for_cdata((string)$content) . ']]>';
        $langblock = self::build_language_block($payload['language'] ?? null);

        return <<<PROMPT
<role>
You are an expert curriculum analyst. You identify the distinct, assessable
concepts in educational material and describe them concisely.
</role>

<task>
Analyse the course content below and extract every distinct, assessable topic.
A topic is a concept, technique, term, or fact that could plausibly be the
subject of one or more quiz questions.
</task>

<content>{$contenttag}</content>{$extra}{$langblock}

<topic_selection_criteria>
  <criterion>Each topic must be specific enough to support several quiz questions.</criterion>
  <criterion>Each topic must be derivable from the content (no external knowledge).</criterion>
  <criterion>Topics must be non-overlapping.</criterion>
  <criterion>Skip generic filler like "introduction" or "conclusion" unless they contain substantive material.</criterion>
  <criterion>Skip exercises, problem statements, and questions that appear in the content.</criterion>
  <criterion>Topic titles and summaries must be written in the output language specified above (or, if none, in the source content's language).</criterion>
</topic_selection_criteria>

<output_format>
Return ONLY a valid JSON object. No prose, no markdown fences.
Schema:

{
  "topics": [
    {"title": "string (concise topic name, 2-8 words)", "summary": "string (1-2 sentences)"}
  ]
}
</output_format>
PROMPT;
    }

    /**
     * Build the prompt for refining a single question.
     */
    private static function build_refine_prompt(array $payload, string $quality): string {
        $question = $payload['question'] ?? null;
        $instructions = $payload['instructions'] ?? 'Improve clarity and pedagogical quality.';
        $serialized = $question ? json_encode($question, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{}';

        $serializedtag = '<![CDATA[' . self::clean_for_cdata((string)$serialized) . ']]>';
        $instructionstag = '<![CDATA[' . self::clean_for_cdata((string)$instructions) . ']]>';
        $langblock = self::build_language_block($payload['language'] ?? null);

        return <<<PROMPT
<role>
You are an expert assessment editor. You refine quiz questions to be clearer,
fairer, and more pedagogically sound without changing what they assess.
</role>

<task>
Refine the question below according to the editor instructions. Keep the
same question type, the same correct answer, and the same conceptual target.
Improve wording, distractor quality, and feedback.
</task>

<current_question>
<serialized>{$serializedtag}</serialized>
</current_question>

<editor_instructions>{$instructionstag}</editor_instructions>

<quality_mode>{$quality}</quality_mode>{$langblock}

<output_format>
Return ONLY a valid JSON array with exactly one element matching the standard
question schema (see other prompts in this plugin). No prose, no markdown.
</output_format>
PROMPT;
    }

    /**
     * Build the prompt for generating additional distractors.
     */
    private static function build_distractors_prompt(array $payload, string $quality): string {
        $question = $payload['question'] ?? null;
        $num = (int)($payload['num_distractors'] ?? 3);
        $serialized = $question ? json_encode($question, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{}';

        $serializedtag = '<![CDATA[' . self::clean_for_cdata((string)$serialized) . ']]>';
        $langblock = self::build_language_block($payload['language'] ?? null);

        return <<<PROMPT
<role>
You are an expert distractor designer. You generate plausible but unambiguously
incorrect answer options for multiple-choice questions, targeting the most
common misconceptions learners hold about the topic.
</role>

<task>
Generate {$num} additional distractor options for the multichoice question below.
The existing correct answer(s) must remain. New distractors must have fraction=0.0
and must be plausible to a learner who has not mastered the topic.
</task>

<current_question>
<serialized>{$serializedtag}</serialized>
</current_question>

<quality_mode>{$quality}</quality_mode>{$langblock}

<output_format>
Return ONLY a valid JSON array of distractor objects:
[
  {"answertext": "string", "fraction": 0.0, "feedback": "string (why this is wrong)"}
]
</output_format>
PROMPT;
    }

    // ─── Language resolution ────────────────────────────────────────────

    /**
     * Resolve a language code (or the "site_default" sentinel) to a
     * normalized ['code' => ..., 'name' => ...] pair, or null when no
     * language was requested.
     *
     * @param string|null $code Raw code from the payload. "site_default"
     *                          resolves to $CFG->lang. Empty / null → null.
     * @return array|null Null when no language is set, or ['code' => 'es',
     *                   'name' => 'Spanish (Español)']
     */
    private static function resolve_language(?string $code): ?array {
        global $CFG;

        $code = trim((string)$code);
        if ($code === '' || $code === 'site_default') {
            $code = $CFG->lang ?? 'en';
        }
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $languages = get_string_manager()->get_list_of_languages();
        $name = $languages[$code] ?? null;
        if ($name === null) {
            // Unknown / uninstalled language pack: still pass it through so
            // the LLM gets the ISO code, but log so admins notice.
            debugging("ai_client: unknown language code '{$code}'", DEBUG_DEVELOPER);
            $name = $code;
        }
        return ['code' => $code, 'name' => $name];
    }

    /**
     * Build the XML block that instructs the LLM to write in a specific
     * language. Returns '' when no language is set, in which case the
     * generic "match topic content" rule still applies.
     *
     * The wizard already resolves "site_default" to a real ISO code before
     * persisting, so a null/empty value here is the legitimate "no
     * language requested" signal (used by legacy requests from before
     * this feature). resolve_language() is still a safety net for direct
     * callers that may pass a sentinel.
     *
     * @param string|null $code Raw code from the payload. "site_default"
     *                          resolves to $CFG->lang. Empty / null → null.
     * @return string XML fragment to inject into the prompt, '' if none
     */
    private static function build_language_block(?string $code): string {
        // Null / empty → no explicit language. Skip the block entirely so
        // the generic "<rule type=language>" applies (legacy behaviour).
        if ($code === null || trim((string)$code) === '') {
            return '';
        }
        $resolved = self::resolve_language($code);
        if ($resolved === null) {
            return '';
        }
        $safecode = htmlspecialchars($resolved['code'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $safename = htmlspecialchars($resolved['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return <<<XML


  <output_language>
    <name>{$safename}</name>
    <code>{$safecode}</code>
  </output_language>
  <instruction>Write every question, answer, feedback, and field in {$safename} (ISO code: {$safecode}), regardless of the source content's language. Translate domain concepts and terminology to {$safename}. Do not preserve source-language phrases.</instruction>
XML;
    }

    // ─── Response parsers ───────────────────────────────────────────────

    /**
     * Convert the raw LLM response into the array of question stdClass
     * objects that the rest of the plugin expects.
     *
     * @param string $response Raw LLM text (may be wrapped in ```json``` fences)
     * @param array $types The question types in the order they were requested
     * @return array Each element is a stdClass matching the plugin's internal
     *               question representation (questiontext, answers, etc.)
     */
    private static function parse_questions_response(string $response, array $types): array {
        $data = self::extract_json($response);

        // Wrap a single-object response in an array.
        if (!isset($data[0]) && isset($data['questiontext'])) {
            $data = [$data];
        }

        $expected = count($types);
        $actual = count($data);
        $count = min($expected, $actual);

        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $q = $data[$i];
            $type = $types[$i] ?? 'multichoice';

            $genfeedback = $q['generalfeedback'] ?? '';
            if (is_array($genfeedback)) {
                $genfeedback = json_encode($genfeedback, JSON_UNESCAPED_UNICODE);
            }

            $obj = new \stdClass();
            $obj->questiontext = (string)($q['questiontext'] ?? '');
            $obj->questiontype = $type;
            $obj->questiontextformat = FORMAT_HTML;
            $obj->generalfeedback = (string)$genfeedback;
            $obj->difficulty = $q['difficulty'] ?? 'medium';
            $obj->blooms_level = $q['blooms_level'] ?? 'understand';
            $obj->ai_reasoning = $q['ai_reasoning'] ?? ($q['rationale'] ?? '');
            $obj->answers = self::normalize_answers($q['answers'] ?? []);
            $obj->status = 'pending';

            if ($type === 'matching' && isset($q['subquestions'])) {
                $obj->subquestions = $q['subquestions'];
            }

            $questions[] = $obj;
        }

        return $questions;
    }

    /**
     * Normalize the AI's answer array into the internal contract that the
     * rest of the plugin (save_question, question_validator, deployer) reads.
     *
     * The prompt asks the model to return objects with the key `answertext`
     * (per the question schema at build_questions_prompt()). The internal
     * contract — and the consumers in question_generator::save_question() —
     * read `text` instead. Without normalization every answer got persisted
     * as an empty string, which is why MCQ/truefalse rendered without
     * options.
     *
     * @param array $rawanswers Raw answers as returned by the LLM
     * @return array Normalized answers: [['text' => ..., 'fraction' => float,
     *               'feedback' => string, 'reasoning' => string], ...]
     */
    private static function normalize_answers(array $rawanswers): array {
        $normalized = [];
        foreach ($rawanswers as $a) {
            if (!is_array($a)) {
                continue;
            }
            $normalized[] = [
                'text'      => (string)($a['answertext'] ?? ($a['text'] ?? '')),
                'fraction'  => (float)($a['fraction'] ?? 0.0),
                'feedback'  => (string)($a['feedback'] ?? ''),
                'reasoning' => (string)($a['reasoning'] ?? ($a['distractor_reasoning'] ?? '')),
            ];
        }
        return $normalized;
    }

    /**
     * Convert the raw LLM response into the array of topic arrays the
     * existing topic_analyzer.php consumes.
     *
     * @param string $response
     * @return array List of ['title' => ..., 'summary' => ...]
     */
    private static function parse_topics_response(string $response): array {
        $data = self::extract_json($response);

        if (empty($data['topics']) || !is_array($data['topics'])) {
            return [];
        }

        $topics = [];
        foreach ($data['topics'] as $t) {
            if (!is_array($t)) {
                continue;
            }
            $title = trim((string)($t['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $topics[] = [
                'title' => $title,
                'summary' => (string)($t['summary'] ?? ''),
            ];
        }

        return $topics;
    }

    /**
     * Convert the raw LLM response into the array of distractor arrays.
     *
     * @param string $response
     * @return array
     */
    private static function parse_distractors_response(string $response): array {
        $data = self::extract_json($response);
        if (!isset($data[0])) {
            return [];
        }
        $out = [];
        foreach ($data as $d) {
            $out[] = [
                'answertext' => (string)($d['answertext'] ?? ''),
                'fraction' => 0.0,
                'feedback' => (string)($d['feedback'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Strip markdown code fences and decode JSON. Returns [] on failure.
     */
    private static function extract_json(string $text): array {
        $text = trim($text);
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $m)) {
            $text = $m[1];
        } else if (preg_match('/```\s*(.*?)\s*```/s', $text, $m)) {
            $text = $m[1];
        }

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            \local_aiquiz_gen\debug_logger::error('AI JSON parse failed', [
                'error' => json_last_error_msg(),
                'preview' => substr($text, 0, 200),
            ]);
            return [];
        }
        return $data;
    }

    // ─── Sanitisation helpers ───────────────────────────────────────────

    /**
     * Strip malformed UTF-8 and 4-byte chars (which MySQL utf8mb3 can't store
     * and which break the XML structure if they happen to contain ']]>').
     *
     * @param string $text
     * @return string
     */
    private static function clean_for_cdata(string $text): string {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $text);
        // CDATA cannot contain the sequence ']]>'.
        $text = str_replace(']]>', ']]]]><![CDATA[>', $text);
        return $text;
    }
}
