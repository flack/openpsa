<?php
/**
 * @package openpsa.setup
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

namespace openpsa\setup;

/**
 * Simple installer class. Sets up directory structures with symlinks and such
 *
 * @package openpsa.setup
 */
class installer
{
    public static function setup_project($event)
    {
        $options = self::get_options($event);
        self::_prepare_dir('src');
        self::_prepare_dir($options['vendor-dir']);
        if (!extension_loaded('midgard2'))
        {
            $io = $event->getIO();
            $config_name = $io->ask('<question>Please enter config name:</question> ');
            $config_file = "/etc/midgard2/conf.d/" . $this->_project_name;
            if (   file_exists($config_file)
                && !$io->askConfirmation('<question>' . $config_file . ' already exists, override?</question> '))
            {
                $config = new midgard_config();
                if (!$config->read_file($config_name, false))
                {
                    throw new \Exception('Could not read config file ' . $config_file);
                }
            }
            else
            {
                $config = self::_create_config($config_name, $io);
            }
        }
    }

    private static function _create_config($config_name, $io)
    {
        $project_basedir = realpath('./');
        $openpsa_basedir = realpath($project_basedir . '/vendor/openpsa/midcom/');

        var_dump($project_basedir, $openpsa_basedir);

        self::_prepare_dir('midgard');
        self::_prepare_dir('midgard/var');
        self::_prepare_dir('midgard/cache');
        self::_prepare_dir('midgard/share');
        self::_prepare_dir('midgard/share/views');
        self::_prepare_dir('midgard/rcs');
        self::_prepare_dir('midgard/blobs');
        self::_prepare_dir('midgard/log');

        self::_link($openpsa_basedir . '/config/midgard_auth_types.xml', $project_basedir . '/midgard/share/midgard_auth_types.xml');
        self::_link($openpsa_basedir . '/config/MidgardObjects.xml', $project_basedir . '/midgard/share/MidgardObjects.xml');

        // Create a config file
        $config = new midgard_config();
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
            throw new \Exception("Failed to save config file " . $config);
        }

        $io->write("Configuration file " . $config_file . " created.");
        return $config;
    }

    public static function install_schemas($event)
    {
        $io = $event->getIO();
        if (!extension_loaded('midgard2'))
        {
            $io->write('<warning>Linking schemas is not yet supported on mgd1, please do this manually if necessary</warning>');
            return;
        }

        $options = self::get_options($event);

        $schemas = self::_get_grandchildren($options['vendor-dir'], 'schemas', 'file');
        $basepath = \midgard_connection::get_instance()->config->sharedir . '/schema/';
        foreach ($schemas as $schema)
        {
            self::_link($schema->getRealPath(), $basepath . $schema->getFilename(), $io);
        }
    }

    public static function install_statics($event)
    {
        $io = $event->getIO();
        $options = self::get_options($event);
        self::_prepare_dir('web/' . $options['midcom-static-dir']);
        self::_prepare_dir($options['static-dir']);
        self::_prepare_dir($options['themes-dir']);

        $basepath = './web/' . $options['midcom-static-dir'] . '/';
        $static_dirs = self::_get_grandchildren($options['vendor-dir'], 'static');

        foreach ($static_dirs as $static)
        {
            self::_link($static->getRealPath(), $basepath . $static->getFilename(), $io);
        }

        $themes = self::_get_children($options['themes-dir']);
        foreach ($themes as $theme)
        {
            if (is_dir($theme->getRealPath() . '/static'))
            {
                self::_link($theme->getRealPath() . '/static', $basepath . $theme->getFilename(), $io);
            }
        }

        $static_dirs = self::_get_children($options['static-dir']);
        foreach ($static_dirs as $dir)
        {
            self::_link($dir->getRealPath(), $basepath . $dir->getFilename(), $io);
        }
    }

    private static function _get_children($parent_dir, $type = 'dir')
    {
        $children = array();
        $iterator = new \DirectoryIterator($parent_dir);
        foreach ($iterator as $child)
        {
            if (   $child->getType() == $type
                && substr($child->getFileName(), 0, 1) !== '.')
            {
                $children[] = $child->getFileInfo();
            }
        }
        return $children;
    }

    /**
     * In fact, this function gets the great-grandchildren, but that doesn't make a good
     * function name
     */
    private static function _get_grandchildren($vendor_dir, $filter, $type = 'dir')
    {
        $return = array();
        $vendors = self::_get_children($vendor_dir);
        foreach ($vendors as $vendor)
        {
            $projects = self::_get_children($vendor->getPathName());
            foreach ($projects as $project)
            {
                if (is_dir($project->getPathName() . '/' . $filter))
                {
                    $return = array_merge($return, self::_get_children($project->getPathName() . '/' . $filter, $type));
                }
            }
        }
        return $return;
    }

    private static function _prepare_dir($dir)
    {
        if (   !is_dir('./' . $dir)
            && !mkdir('./' . $dir))
        {
            throw new \Exception('could not create ' . $dir);
        }
    }

    private static function _link($target, $linkname, $io)
    {
        if (is_link($linkname))
        {
            unlink($linkname);
        }
        if (!@symlink($target, $linkname))
        {
            throw new \Exception('could not link ' . $target . ' to ' . $linkname);
        }
        if ($io->isVerbose())
        {
            $io->write('linked ' . $target . ' to ' . $linkname);
        }
    }

    protected static function get_options($event)
    {
        $options = array_merge(array
        (
            'midcom-static-dir' => 'midcom-static',
            'static-dir' => 'static',
            'vendor-dir' => 'vendor',
            'themes-dir' => 'themes',
            'schema-dir' => 'schemas'
        ), $event->getComposer()->getPackage()->getExtra());

        return $options;
    }
}
?>