<?php  // Moodle configuration file
//
// Reads database and site settings from environment variables
// injected by docker-compose.yml. Edit values in .env, not here.

unset($CFG);
global $CFG;
$CFG = new stdClass();

// --- Database ---------------------------------------------------------
$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DOCKER_DBHOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DOCKER_DBNAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DOCKER_DBUSER') ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DOCKER_DBPASS') ?: '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist'   => 0,
    'dbport'      => 3306,
    'dbsocket'    => '',
    'dbcollation' => getenv('MOODLE_DOCKER_DBCOLLATION') ?: 'utf8mb4_bin',
];

// --- Site URL ---------------------------------------------------------
// Protocol can be "http" or "https". When behind a reverse proxy
// (Caddy, Traefik, nginx) set MOODLE_DOCKER_BEHIND_PROXY=1 so Moodle
// honours the X-Forwarded-Proto header.
$protocol = strtolower(getenv('MOODLE_DOCKER_WEB_PROTOCOL') ?: 'http');
$protocol = in_array($protocol, ['http', 'https'], true) ? $protocol : 'http';

$host = getenv('MOODLE_DOCKER_WEB_HOST') ?: 'localhost';
$port = getenv('MOODLE_DOCKER_WEB_PORT') ?: '';
// explode() can return more than one part when the value is "bind_ip:port";
// end() requires a variable, not an expression (PHP 7+).
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
    // Tells Moodle to trust X-Forwarded-Proto / X-Forwarded-For from
    // the reverse proxy so https://wwwroot is preserved end-to-end.
    $CFG->sslproxy = true;
}

// --- Paths and core settings -----------------------------------------
$CFG->dataroot              = '/var/www/moodledata';
$CFG->admin                 = 'admin';
$CFG->directorypermissions  = 02777;

// --- Developer-friendly defaults -------------------------------------
$CFG->debug          = E_ALL;             // DEBUG_DEVELOPER
$CFG->debugdisplay   = 1;
$CFG->debugstringids = 1;
$CFG->perfdebug      = 15;
$CFG->allowthemechangeonurl = 1;
$CFG->passwordpolicy = 0;                // dev only — relax password rules
$CFG->pathtophp      = '/usr/local/bin/php';

require_once(__DIR__ . '/lib/setup.php');
