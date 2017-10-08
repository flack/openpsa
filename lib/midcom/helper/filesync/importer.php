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

    public $root_dir;

    /**
     * Initializes the class.
     *
     * @param string $root_dir The import root directory
     * @param boolean $delete_missing whether to delete missing items from database
     */
    public function __construct($root_dir, $delete_missing = false)
    {
        $this->delete_missing = $delete_missing;
        $this->root_dir = $root_dir;

        parent::__construct();
    }

    public function run()
    {
        $ip_sudo = midcom::get()->auth->require_admin_or_ip($this->_component);
        midcom::get()->header('Content-Type: text/plain');

        $this->import();
        echo "Import from {$this->root_dir} completed\n";
        if ($ip_sudo) {
            midcom::get()->auth->drop_sudo();
        }
    }

    protected function _read_dirs($path)
    {
        $foldernames = [];
        $nodes = [];
        foreach (glob($path . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $dirname) {
            $nodes[] = $dirname;
            $foldernames[] = basename($dirname);
        }

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, 0);
        }
        return $nodes;
    }

    protected function _get_node($classname, $parent_id, $path)
    {
        $name = basename($path);
        $object_qb = midcom::get()->dbfactory->new_query_builder($classname);
        $object_qb->add_constraint('up', '=', $parent_id);
        $object_qb->add_constraint('name', '=', $name);
        if ($object_qb->count() == 0) {
            // New node
            $node = new $classname();
            $node->up = $parent_id;
            $node->name = $name;
            if (!$node->create()) {
                throw new midcom_error(midcom_connection::get_error_string());
            }
        } else {
            $node = $object_qb->get_result(0);
        }
        return $node;
    }

    protected function read_file($path)
    {
        // Deal with element
        $file_contents = file_get_contents($path);
        $encoding = mb_detect_encoding($file_contents);
        if ($encoding != 'UTF-8') {
            $file_contents = @iconv($encoding, 'UTF-8', $file_contents);
        }
        return $file_contents;
    }

    /**
     * Dynamically create importer instances.
     * The returned instances will be created but not initialized.
     *
     * @param string $type type
     * @return midcom_helper_filesync_importer The newly created importer instance.
     */
    public static function create($type)
    {
        $classname = "midcom_helper_filesync_importer_{$type}";
        if (!class_exists($classname)) {
            throw new midcom_error("Requested importer class {$type} is not installed.");
        }

        return new $classname(midcom_helper_filesync_interface::prepare_dir($type));
    }

    public function delete_missing_folders(array $foldernames, $parent_id)
    {
        $qb = $this->get_node_qb($parent_id);
        $qb->add_constraint('name', 'NOT IN', $foldernames);

        foreach ($qb->execute() as $folder) {
            $folder->delete();
        }
    }

    public function delete_missing_files(array $filenames, $parent_id)
    {
        $qb = $this->get_leaf_qb($parent_id);
        $qb->add_constraint('name', 'NOT IN', $filenames);

        foreach ($qb->execute() as $file) {
            $file->delete();
        }
    }

    /**
     * Run the import
     */
    abstract public function import();

    /**
     * Returns node QB
     *
     * @param integer $parent_id The parent's ID
     * @return midcom_core_querybuilder The prepared QB instance
     */
    abstract public function get_node_qb($parent_id);

    /**
     * Returns leaf QB
     *
     * @param integer $parent_id The parent's ID
     * @return midcom_core_querybuilder The prepared QB instance
     */
    abstract public function get_leaf_qb($parent_id);
}
