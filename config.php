<?php  // Moodle configuration file
//
// Reads every value from environment variables injected by
// docker-compose. Configure via .env (dev) or your platform's
// env var UI (Dokploy / Portainer / etc.) — never edit this file.

unset($CFG);
global $CFG;
$CFG = new stdClass();

// --- Database ---------------------------------------------------------
$CFG->dbtype    = getenv('MOODLE_DOCKER_DBTYPE') ?: 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DOCKER_DBHOST') ?: 'db';
$cfgDbport      = getenv('MOODLE_DOCKER_DBPORT') ?: '3306';
$CFG->dbname    = getenv('MOODLE_DOCKER_DBNAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DOCKER_DBUSER') ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DOCKER_DBPASS') ?: '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist'   => 0,
    'dbport'      => (int)$cfgDbport,
    'dbsocket'    => '',
    'dbcollation' => getenv('MOODLE_DOCKER_DBCOLLATION') ?: 'utf8mb4_bin',
];
unset($cfgDbport);

// --- Site URL ---------------------------------------------------------
// Protocol can be "http" or "https". When behind a reverse proxy
// (Traefik in Dokploy, Caddy in dev) set MOODLE_DOCKER_BEHIND_PROXY=1
// so Moodle honours the X-Forwarded-Proto header.
$protocol = strtolower(getenv('MOODLE_DOCKER_WEB_PROTOCOL') ?: 'http');
$protocol = in_array($protocol, ['http', 'https'], true) ? $protocol : 'http';

$host = getenv('MOODLE_DOCKER_WEB_HOST') ?: 'localhost';
$port = getenv('MOODLE_DOCKER_WEB_PORT') ?: '';
$portParts = explode(':', $port);
$port = end($portParts);

// Skip the port suffix if it matches the protocol default — keeps
// $CFG->wwwroot clean (https://host, not https://host:443).
$portSuffix = '';
if ($port !== '' && (string)(int)$port === (string)$port) {
    $defaultPort = ($protocol === 'https') ? '443' : '80';
    if ($port !== $defaultPort) {
        $portSuffix = ':' . $port;
    }
}

$CFG->wwwroot = $protocol . '://' . $host . $portSuffix;

if (getenv('MOODLE_DOCKER_BEHIND_PROXY') === '1') {
    $CFG->sslproxy = true;
}

// --- Paths and core settings -----------------------------------------
$CFG->dataroot              = '/var/www/moodledata';
$CFG->admin                 = 'admin';
$CFG->directorypermissions  = 02777;
$CFG->pathtophp             = '/usr/local/bin/php';

// --- Router (Moodle 5.x) ---------------------------------------------
// Suppresses the "Router not configured" env check warning. Moodle 5.x
// uses a Slim-based router and expects a boolean flag here, NOT a
// class FQCN. The actual URL rewriting must be done by the web
// server (Apache: mod_rewrite; Caddy: handle_path). See
// https://docs.moodle.org/en/Configuring_the_Router
$CFG->routerconfigured = true;

// --- Debug vs production ---------------------------------------------
// MOODLE_DEBUG=1 enables verbose developer output; anything else
// (or unset) means production-safe defaults. Compose files set this
// per environment.
$debug = getenv('MOODLE_DEBUG') === '1';

$CFG->debug                 = $debug ? E_ALL : 0;
$CFG->debugdisplay          = $debug ? 1 : 0;
$CFG->debugstringids        = $debug ? 1 : 0;
$CFG->perfdebug             = $debug ? 15 : 0;
$CFG->allowthemechangeonurl = $debug ? 1 : 0;
// Strict password policy in prod; relax only for local dev.
$CFG->passwordpolicy        = $debug ? 0 : 1;

require_once(__DIR__ . '/lib/setup.php');
