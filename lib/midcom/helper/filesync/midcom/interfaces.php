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
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_component = 'midcom.helper.filesync';

        // Load all mandatory class files of the component here
        $this->_autoload_files = array();

        // Load all libraries used by component here
        $this->_autoload_libraries = array();
    }

    public static function prepare_dir($prefix)
    {
        $config = midcom_baseclasses_components_configuration::get('midcom.helper.filesync', 'config');
        $path = $config->get('filesync_path');
        if (!file_exists($path))
        {
            $parent = dirname($path);
            if (!is_writable($parent))
            {
                throw new midcom_error("Directory {$parent} is not writable by Apache");
                // This will exit.
            }

            if (! mkdir($path))
            {
                throw new midcom_error("Failed to create directory {$path}. Reason: " . $php_errormsg);
                // This will exit.
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
                throw new midcom_error("Directory {$path} is not writable by Apache");
                // This will exit.
            }

             if (! mkdir($module_dir))
            {
                throw new midcom_error("Failed to create directory {$module_dir}. Reason: " . $php_errormsg);
                // This will exit.
            }
        }

        return "{$module_dir}/";
    }
}
?>