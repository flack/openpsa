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
        self::_prepare_dir('web/' . $options['midcom-static-dir']);
        self::_prepare_dir($options['static-dir']);
        self::_prepare_dir($options['themes-dir']);
    }

    public static function install_schemas($event)
    {
        $io = $event->getIO();
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