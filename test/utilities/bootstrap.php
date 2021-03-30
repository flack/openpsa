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
    // @todo: Really we should overwrite nernel.logs_dir here
    'log_filename' => OPENPSA2_UNITTEST_OUTPUT_DIR . '/var/log/midcom.log'
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

$midcom = midcom::init('test', true);

if (class_exists('org_openpsa_core_siteconfig')) {
    org_openpsa_core_siteconfig::get_instance();
}

// Clean up residue cache entries from previous runs
$midcom->cache->invalidate_all();
$midcom->reboot(null);
