<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Import a site style from the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_importer_style extends midcom_helper_filesync_importer
{
    private function read_style($path, $parent_id)
    {
        $style = $this->_get_node('midcom_db_style', $parent_id, $path);

        $directory = dir($path);
        $foldernames = [];
        $filenames = [];
        while (false !== ($entry = $directory->read())) {
            if (substr($entry, 0, 1) == '.') {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}")) {
                // Recurse deeper
                $this->read_style("{$path}/{$entry}", $style->id);
                $foldernames[] = $entry;
            }

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2) {
                continue;
            }
            $element_name = $filename_parts[0];
            $field = false;
            switch ($filename_parts[count($filename_parts) - 1]) {
                case 'php':
                    $field = 'value';
                    break;
                default:
                    continue;
            }

            $filenames[] = $element_name;
            $file_contents = $this->read_file("{$path}/{$entry}");

            $qb = $this->get_leaf_qb($style->id);
            $qb->add_constraint('name', '=', $element_name);
            if ($qb->count() == 0) {
                // New element
                $element = new midcom_db_element();
                $element->style = $style->id;
                $element->name = $element_name;
                $element->$field = $file_contents;
                $element->create();
                continue;
            }

            $elements = $qb->execute();
            $element = $elements[0];

            // Update existing elements only if they have actually changed
            if ($element->$field != $file_contents) {
                $element->$field = $file_contents;
                $element->update();
            }
        }
        $directory->close();

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $style->id);
            $this->delete_missing_files($filenames, $style->id);
        }
    }

    public function get_leaf_qb($parent_id)
    {
        $qb = midcom_db_element::new_query_builder();
        $qb->add_constraint('style', '=', $parent_id);
        return $qb;
    }

    public function get_node_qb($parent_id)
    {
        $qb = midcom_db_style::new_query_builder();
        $qb->add_constraint('up', '=', $parent_id);
        return $qb;
    }

    public function import()
    {
        $nodes = $this->_read_dirs($this->root_dir);
        foreach ($nodes as $node) {
            $this->read_style($node, 0);
        }
    }
}
