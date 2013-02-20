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
    public $delete_missing = false;

    public $root_dir = '';

    /**
     * Initializes the class.
     *
     * @param string $type The importer type
     * @param boolean $delete_missing whether to delete missing items from database
     */
    public function __construct($type, $delete_missing = false)
    {
         $this->delete_missing = $delete_missing;
         $this->root_dir = midcom_helper_filesync_interface::prepare_dir($type);

         parent::__construct();
    }

    protected function _get_node($classname, $parent_id, $path)
    {
        $name = basename($path);
        $object_qb = midcom::get('dbfactory')->new_query_builder($classname);
        $object_qb->add_constraint('up', '=', $parent_id);
        $object_qb->add_constraint('name', '=', $name);
        if ($object_qb->count() == 0)
        {
            // New node
            $node = new $classname();
            $node->up = $parent_id;
            $node->name = $name;
            if (!$node->create())
            {
                throw new midcom_error(midcom_connection::get_error_string());
            }
        }
        else
        {
            $nodes = $object_qb->execute();
            $node = $nodes[0];
        }
        return $node;
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
        }

        $class = new $classname($type);
        return $class;
    }

    /**
     * Run the import
     */
    abstract public function import();
}
?>