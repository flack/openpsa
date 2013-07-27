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
    protected static $_client;

    protected static function _get_client($event)
    {
        if (empty(self::$_client))
        {
            $options = self::get_options($event);
            if (extension_loaded('midgard2'))
            {
                self::$_client = new mgd2installer($event->getIO(), $options['vendor-dir']);
            }
            else if (!extension_loaded('midgard'))
            {
                throw new \RunTimeException('midgard extension is not loaded');
            }
        }
        return self::$_client;
    }

    public static function setup_project($event)
    {
        self::_prepare_dir('src');
        if ($client = self::_get_client($event))
        {
            $client->init_project();
        }
    }

    public static function install_schemas($event)
    {
        $io = $event->getIO();
        if (extension_loaded('midgard'))
        {
            $io->write('<warning>Linking schemas is not yet supported on mgd1, please do this manually if necessary</warning>');
            return;
        }

        $client = self::_get_client($event);
        $basepath = $client->get_schema_dir();

        $options = self::get_options($event);
        $schemas = self::_get_grandchildren($options['vendor-dir'], 'schemas', 'file');

        foreach ($schemas as $schema)
        {
            self::_link($schema->getRealPath(), $basepath . $schema->getFilename(), $io);
        }
    }

    public static function install_statics($event)
    {
        $io = $event->getIO();
        $options = self::get_options($event);
        self::_prepare_dir('web');
        self::_prepare_dir('web/' . $options['midcom-static-dir']);
        self::_prepare_dir($options['static-dir']);
        self::_prepare_dir($options['themes-dir']);

        $basepath = './web/' . $options['midcom-static-dir'] . '/';
        $static_dirs = self::_get_grandchildren($options['vendor-dir'], 'static');
        $project = new \SplFileInfo('.');
        $prefix = strlen($project->getPathname()) - 1;
        foreach ($static_dirs as $static)
        {
            $relative_path = '../../' . substr($static->getPathname(), $prefix);
            self::_link($relative_path, $basepath . $static->getFilename(), $io);
        }

        $themes = self::_get_children($options['themes-dir']);
        foreach ($themes as $theme)
        {
            if (is_dir($theme->getRealPath() . '/static'))
            {
                $relative_path = '../../' . substr($theme->getPathname(), $prefix);
                self::_link($relative_path . '/static', $basepath . $theme->getFilename(), $io);
            }
        }

        $static_dirs = self::_get_children($options['static-dir']);
        foreach ($static_dirs as $dir)
        {
            $relative_path = '../../' . substr($dir->getPathname(), $prefix);

            self::_link($relative_path, $basepath . $dir->getFilename(), $io);
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

    protected static function _prepare_dir($dir)
    {
        if (   !is_dir('./' . $dir)
            && !mkdir('./' . $dir))
        {
            throw new \Exception('could not create ' . $dir);
        }
    }

    protected static function _link($target, $linkname, $io)
    {
        if (is_link($linkname))
        {
            if (!file_exists(realpath($linkname)))
            {
                $io->write('Link in <info>' . basename($target) . '</info> points to nonexistant path <comment>' . realpath($linkname) . '</comment>, removing');
                @unlink($linkname);
            }
            else
            {
                if (   realpath($linkname) !== $target
                    && md5_file(realpath($linkname)) !== md5_file($target))
                {
                    $io->write('Skipping <info>' . basename($target) . '</info>: Found Link in <info>' . dirname($linkname) . '</info> to <comment>' . realpath($linkname) . '</comment>');
                }
                return;
            }
        }
        else if (is_file($linkname))
        {
            if (md5_file($linkname) !== md5_file($target))
            {
                $io->write('Skipping <info>' . basename($target) . '</info>: Found existing file in <comment>' . dirname($linkname) . '</comment>');
            }
            return;
        }
        if (!@symlink($target, $linkname))
        {
            throw new \Exception('could not link ' . $target . ' to ' . $linkname);
        }
        if ($io->isVerbose())
        {
            $io->write('Linked <info>' . $target . '</info> to <comment>' . $linkname . '</comment>');
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