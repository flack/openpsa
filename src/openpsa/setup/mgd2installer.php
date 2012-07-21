<?php
/**
 * @package openpsa.setup
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

namespace openpsa\setup;

/**
 * Simple installer class. Sets up a mgd2 configuration and DB
 *
 * @package openpsa.setup
 */
class mgd2installer extends installer
{
    protected $_io;

    public function __construct($io)
    {
        $this->_io = $io;
    }

    public function run()
    {
        $config_name = $this->_io->ask('<question>Please enter config name:</question> ');
        $config_file = "/etc/midgard2/conf.d/" . $config_name;
        if (   file_exists($config_file)
            && !$this->_io->askConfirmation('<question>' . $config_file . ' already exists, override?</question> '))
        {
            $config = new \midgard_config();
            if (!$config->read_file($config_name, false))
            {
                throw new \Exception('Could not read config file ' . $config_file);
            }
        }
        else
        {
            $config = $this->_create_config($config_name);
        }

        $this->_prepare_database($config);
    }

    private function _prepare_database(\midgard_config $config)
    {
        if (!$config->create_blobdir())
        {
            throw new \Exception("Failed to create file attachment storage directory to {$config->blobdir}:" . \midgard_connection::get_instance()->get_error_string());
        }

        // Create storage
        if (!\midgard_storage::create_base_storage())
        {
            if (\midgard_connection::get_instance()->get_error_string() != 'MGD_ERR_OK')
            {
                throw new \Exception("Failed to create base database structures" . \midgard_connection::get_instance()->get_error_string());
            }
        }

        $re = new \ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            if (!$refclass->isSubclassOf('midgard_object'))
            {
                continue;
            }
            $type = $refclass->getName();

            \midgard_storage::create_class_storage($type);
            \midgard_storage::update_class_storage($type);
        }
    }

    private function _create_config($config_name)
    {
        $project_basedir = realpath('./');
        $openpsa_basedir = realpath($project_basedir . '/vendor/openpsa/midcom/');

        self::_prepare_dir('midgard');
        self::_prepare_dir('midgard/var');
        self::_prepare_dir('midgard/cache');
        self::_prepare_dir('midgard/share');
        self::_prepare_dir('midgard/share/views');
        self::_prepare_dir('midgard/rcs');
        self::_prepare_dir('midgard/blobs');
        self::_prepare_dir('midgard/log');

        self::_link($openpsa_basedir . '/config/midgard_auth_types.xml', $project_basedir . '/midgard/share/midgard_auth_types.xml', $this->_io);
        self::_link($openpsa_basedir . '/config/MidgardObjects.xml', $project_basedir . '/midgard/share/MidgardObjects.xml', $this->_io);

        // Create a config file
        $config = new \midgard_config();
        $config->dbtype = 'MySQL';
        $config->database = $config_name;
        $config->blobdir = $project_basedir . '/midgard/blobs';
        $config->sharedir = $project_basedir . '/midgard/share';
        $config->vardir = $project_basedir . '/midgard/var';
        $config->cachedir = $project_basedir . '/midgard/cache';
        $config->logfilename = $project_basedir . 'midgard/log/midgard.log';
        $config->loglevel = 'debug';
        if (!$config->save_file($config_name, false))
        {
            throw new \Exception("Failed to save config file " . $config_name);
        }

        $this->_io->write("Configuration file " . $config_name . " created.");
        return $config;
    }
}
?>