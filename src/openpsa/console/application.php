<?php
/**
 * @package openpsa.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\console;

use Symfony\Component\Console\Application as base_application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use openpsa\console\command\exec;

/**
 * OpenPSA CLI command runner
 *
 * @package openpsa.console
 */
class application extends base_application
{
    /**
     * @inheritDoc
     */
    public function __construct($name = __CLASS__, $version = '9.0beta5+git')
    {
        parent::__construct($name, $version);

        $this->_prepare_environment();
        $this->_add_default_commands();
        $this->getDefinition()
            ->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'Config name (mgd2 only)'));
    }

    /**
     * @inheritDoc
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $config_name = $input->getParameterOption(array('--config', '-c'), null);
        if (!\midcom_connection::setup(OPENPSA_PROJECT_BASEDIR, $config_name))
        {
            throw new \RuntimeException('Could not open midgard connection ' . $config_name . ': ' . \midcom_connection::get_error_string());
        }

        parent::doRun($input, $output);
    }

    private function _prepare_environment()
    {
        if (file_exists(OPENPSA_PROJECT_BASEDIR . 'config.inc.php'))
        {
            include_once OPENPSA_PROJECT_BASEDIR . 'config.inc.php';
        }
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
    }

    private function _add_default_commands()
    {
        $loader = \midcom::get('componentloader');
        $this->_process_dir(MIDCOM_ROOT . '/midcom/exec', 'midcom');
        foreach ($loader->manifests as $manifest)
        {
            $exec_dir = $loader->path_to_snippetpath($manifest->name) . '/exec';
            $this->_process_dir($exec_dir, $manifest->name);
        }
    }

    private function _process_dir($exec_dir, $component)
    {
        if (is_dir($exec_dir))
        {
            foreach (glob($exec_dir . '/*.php') as $file)
            {
                $command = substr(basename($file), 0, -4);
                $this->add(new exec($component . ':' . $command));
            }
        }
    }
}
