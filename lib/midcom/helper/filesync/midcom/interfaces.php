<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for midcom.helper.filesync
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_interface extends midcom_baseclasses_components_interface
{
    public static function prepare_dir($prefix)
    {
        $config = midcom_baseclasses_components_configuration::get('midcom.helper.filesync', 'config');
        $path = $config->get('filesync_path');
        if (!file_exists($path))
        {
            $parent = dirname($path);
            if (!is_writable($parent))
            {
                throw new midcom_error("Directory {$parent} is not writable");
            }

            if (! mkdir($path))
            {
                throw new midcom_error("Failed to create directory {$path}. Reason: " . $php_errormsg);
            }
        }

        if (substr($path, -1) != '/')
        {
            $path .= '/';
        }

        $module_dir = "{$path}{$prefix}";
        if (!file_exists($module_dir))
        {
            if (!is_writable($path))
            {
                throw new midcom_error("Directory {$path} is not writable");
            }

             if (! mkdir($module_dir))
            {
                throw new midcom_error("Failed to create directory {$module_dir}. Reason: " . $php_errormsg);
            }
        }

        return "{$module_dir}/";
    }
}
?>