<?php
/**
 * Some generic setup code to prepare an environment for running unit tests
 *
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

$defaults = [
    'theme' => 'OpenPsa2',
    'midcom_services_rcs_root' => OPENPSA2_UNITTEST_OUTPUT_DIR . '/rcs',
    'cache_base_directory' => OPENPSA2_UNITTEST_OUTPUT_DIR . '/cache/',
    'midcom_tempdir' => OPENPSA2_UNITTEST_OUTPUT_DIR . '/tmp/',
    'log_filename' => OPENPSA2_UNITTEST_OUTPUT_DIR . '/midcom.log'
];

$GLOBALS['midcom_config_local'] = array_merge($defaults, $GLOBALS['midcom_config_local']);
$GLOBALS['midcom_config_local']['attachment_cache_url'] = '/blobcache';
$GLOBALS['midcom_config_local']['attachment_cache_root'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/cache/blobs';
$GLOBALS['midcom_config_local']['attachment_cache_root'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/cache/blobs';
$GLOBALS['midcom_config_local']['cache_module_content_headers_strategy'] = 'no-cache';

$_SERVER = [
    'HTTP_HOST' => 'localhost',
    'SERVER_NAME' => 'localhost',
    'SERVER_SOFTWARE' => 'PHPUnit',
    'HTTP_USER_AGENT' => 'PHPUnit',
    'SERVER_PORT' => '80',
    'REMOTE_ADDR' => 'unittest dummy connection',
    'REQUEST_URI' => '/midcom-test-init',
    'REQUEST_TIME' => time(),
    'REMOTE_PORT' => '12345',
    'SCRIPT_NAME' => 'unittest-run'
];

midcom::register_service_class('session', mock_sessioning::class);

// This is a bit awkward, but makes life simpler until we've transitioned more fully to the
// httpkernel infrastructure
$GLOBALS['kernel'] = midcom::init('test', true);

// Clean up residue cache entries from previous runs
midcom::get()->cache->invalidate_all();
$GLOBALS['kernel']->reboot(null);
