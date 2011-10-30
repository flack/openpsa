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
abstract class midcom_helper_filesync_importer extends midcom_baseclasses_components_purecode
{
    /**
     * Whether to delete elements from database that do not exist in the file system
     *
     * @var boolean
     */
    var $delete_missing = false;

    /**
     * Initializes the class.
     *
     * @param boolean $delete_missing whether to delete missing items from database
     */
    public function __construct($delete_missing = false)
    {
         $this->_component = 'midcom.helper.filesync';
         $this->delete_missing = $delete_missing;
         parent::__construct();
    }

    /**
     * This is a static factory method which lets you dynamically create importer instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * @param string $type type
     * @return midcom_helper_filesync_importer A reference to the newly created importer instance.
     */
    public static function & create($type)
    {
        $classname = "midcom_helper_filesync_importer_{$type}";
        if (!class_exists($classname))
        {
            throw new midcom_error("Requested importer class {$type} is not installed.");
            // This will exit.
        }

        $class = new $classname();
        return $class;
    }

    /**
     * Run the import
     */
    abstract public function import();
}
?>