<?php
/**
 * Some generic setup code to prepare an environment for running unit tests
 *
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once MIDCOM_ROOT . '/../test/utilities/testcase.php';
require_once MIDCOM_ROOT . '/../test/utilities/helpers.php';

define('OPENPSA2_UNITTEST_RUN', true);
define('OPENPSA2_UNITTEST_OUTPUT_DIR', OPENPSA_TEST_ROOT . '__output');

if (file_exists(OPENPSA2_UNITTEST_OUTPUT_DIR))
{
    $ret = false;
    $output = system('rm -R ' . OPENPSA2_UNITTEST_OUTPUT_DIR, $ret);

    if ($ret)
    {
        throw new Exception('Could not remove old output dir: ' . $output);
    }
}

if (!mkdir(OPENPSA2_UNITTEST_OUTPUT_DIR))
{
    throw new Exception('could not create output directory');
}
if (!mkdir(OPENPSA2_UNITTEST_OUTPUT_DIR . '/rcs'))
{
    throw new Exception('could not create output RCS directory');
}
if (!mkdir(OPENPSA2_UNITTEST_OUTPUT_DIR . '/cache'))
{
    throw new Exception('could not create output cache directory');
}

if (empty($GLOBALS['midcom_config_local']['theme']))
{
    $GLOBALS['midcom_config_local']['theme'] = 'OpenPsa2';
}
if (empty($GLOBALS['midcom_config_local']['midcom_services_rcs_root']))
{
    $GLOBALS['midcom_config_local']['midcom_services_rcs_root'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/rcs';
}
if (empty($GLOBALS['midcom_config_local']['cache_base_directory']))
{
    $GLOBALS['midcom_config_local']['cache_base_directory'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/cache/';
}
if (empty($GLOBALS['midcom_config_local']['log_filename']))
{
    $GLOBALS['midcom_config_local']['log_filename'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/midcom.log';
}

if (!defined('OPENPSA2_PREFIX'))
{
    define('OPENPSA2_PREFIX', dirname($_SERVER['SCRIPT_NAME']) . '/..');
}
if (! defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/openpsa2-static');
}

$_SERVER = array
(
	'HTTP_HOST' => 'localhost',
	'SERVER_NAME' => 'localhost',
	'SERVER_SOFTWARE' => 'PHPUnit',
	'HTTP_USER_AGENT' => 'PHPUnit',
	'SERVER_PORT' => '80',
	'REMOTE_ADDR' => 'unittest dummy connection',
	'REQUEST_URI' => '/midcom-test-init',
	'REQUEST_TIME' => time()
);

// Include the MidCOM environment for running OpenPSA
require MIDCOM_ROOT . '/midcom.php';

//Clean up residue cache entries from previous runs
midcom::get('cache')->invalidate_all();
?>