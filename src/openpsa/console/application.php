<?php
/**
 * @package openpsa.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\console;

use Symfony\Component\Console\Application as base_application;
use openpsa\console\command\exec;

/**
 * OpenPSA CLI command runner
 *
 * @package openpsa.console
 */
class application extends base_application
{
    public function __construct($name = __CLASS__, $version = '9.0beta5+git')
    {
        parent::__construct($name, $version);

        $this->_prepare_environment();
        $this->_add_default_commands();
    }

    private function _prepare_environment()
    {
        if (!defined('OPENPSA2_PREFIX'))
        {
            define('OPENPSA2_PREFIX', '/');
        }

        $server_defaults = array
        (
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_SOFTWARE' => __CLASS__,
            'HTTP_USER_AGENT' => $this->getName(),
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/',
            'REQUEST_TIME' => time(),
            'REMOTE_PORT' => '80'
        );
        $_SERVER = array_merge($server_defaults, $_SERVER);

        \midcom_connection::setup(OPENPSA_PROJECT_BASEDIR);
    }

    private function _add_default_commands()
    {
        $this->add(new exec);
    }
}
