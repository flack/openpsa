<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.helper.filesync
 */
abstract class midcom_helper_filesync_exporter extends midcom_baseclasses_components_purecode
{
    /**
     * Whether to delete elements from file system that do not exist in database
     *
     * @var boolean
     */
    public $delete_missing = false;

    public $root_dir;

    /**
     * Initializes the class.
     *
     * @param string $root_dir The export root directory
     * @param boolean $delete_missing whether to delete missing items from database
     */
    public function __construct($root_dir, $delete_missing = false)
    {
         $this->delete_missing = $delete_missing;
         $this->root_dir = $root_dir;

         parent::__construct();
    }

    abstract public function export();

    protected function delete_missing_folders(array $foldernames, $path)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $directory = dir($path);
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (!is_dir("{$path}/{$entry}"))
            {
                // We're only checking for directories here
                continue;
            }

            if (!in_array($entry, $foldernames))
            {
                unlink("{$path}/{$entry}");
            }
        }
        $directory->close();
    }

    protected function delete_missing_files(array $filenames, $path)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $directory = dir($path);
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // We're only checking for files here
                continue;
            }

            if (!in_array($entry, $filenames))
            {
                unlink("{$path}/{$entry}");
            }
        }
        $directory->close();
    }

    /**
     * Dynamically create exporter instances.
     * The returned instances will be created, but not initialized.
     *
     * @param string $type type
     * @return midcom_helper_filesync_exporter The newly created exporter instance.
     */
    public static function create($type)
    {
        $classname = "midcom_helper_filesync_exporter_{$type}";
        if (!class_exists($classname))
        {
            throw new midcom_error("Requested exporter class {$type} is not installed.");
        }

        return new $classname(midcom_helper_filesync_interface::prepare_dir($type));
    }
}
