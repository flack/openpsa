<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * "Classic" midcom autoloader
 *
 * @package midcom.compat
 */
class midcom_compat_autoloader
{
    public function __construct($prepend = true)
    {
        spl_autoload_register(array($this, 'autoload'), true, $prepend);
    }

    /**
     * Automatically load missing class files
     *
     * @param string $class_name Name of a missing PHP class
     */
    public function autoload($class_name)
    {
        static $autoloaded = 0;

        if (preg_match('/_dba?$/', $class_name))
        {
            // DBA object files are named objectname.php

            // Ensure we have the component loaded
            if (!midcom::get()->dbclassloader->load_component_for_class($class_name))
            {
                // Failed to load the component
                return;
            }
            if (class_exists($class_name))
            {
                return;
            }

            $class_name = preg_replace('/_dba?$/', '', $class_name);
        }
        else if (   preg_match('/^[^_]+?_[^_]+?_[^_]+?_interface$/', $class_name)
                 && $class_name != 'midcom_baseclasses_components_interface')
        {
            // MidCOM component interfaces are named midcom/interface.php
            midcom::get()->dbclassloader->load_component_for_class($class_name);
            return;
        }

        $path = $this->_resolve_path($class_name);

        if (!$path)
        {
            return;
        }

        require $path;
        $autoloaded++;
    }

    private function _resolve_path($classname)
    {
        $path = str_replace('//', '/_', str_replace('_', '/', $classname)) . '.php';
        if (file_exists(MIDCOM_ROOT . '/' . $path))
        {
            return MIDCOM_ROOT . '/' . $path;
        }
        else
        {
            $alternative_path = str_replace('.php', '/main.php', $path);
            if (file_exists(MIDCOM_ROOT . '/' . $alternative_path))
            {
                return MIDCOM_ROOT . '/' . $alternative_path;
            }
        }
        // file was not found in-tree, let's look somewhere else
        $component = preg_replace('|^([a-z].+?)/(.+?)/([^/\.]+).*$|', '$1.$2.$3', $path);

        if (midcom::get()->componentloader->is_installed($component))
        {
            $component_path = midcom::get()->componentloader->path_to_snippetpath($component);
            $class_part = preg_replace('|^/|', '', substr($path, strlen($component)));
            $path = str_replace('/.php', '.php', $component_path . '/' . $class_part);

            if (file_exists($path))
            {
                return $path;
            }
            else
            {
                $alternative_path = str_replace('.php', '/main.php', $path);
                if (file_exists($alternative_path))
                {
                    return $alternative_path;
                }
            }
        }

        return false;
    }
}