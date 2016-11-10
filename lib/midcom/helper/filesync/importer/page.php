<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Import pages from the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_importer_page extends midcom_helper_filesync_importer
{
    private function read_page($path, $parent_id)
    {
        $page = $this->_get_node('midcom_db_page', $parent_id, $path);

        $directory = dir($path);
        $foldernames = array();
        $filenames = array();
        while (false !== ($entry = $directory->read())) {
            if (substr($entry, 0, 1) == '.') {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}")) {
                // Recurse deeper
                $this->read_page("{$path}/{$entry}", $page->id);
                $foldernames[] = $entry;
                continue;
            }

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2) {
                continue;
            }
            $pageelement_name = $filename_parts[0];

            $field = false;
            switch ($filename_parts[count($filename_parts) - 1]) {
                case 'php':
                    $field = 'value';
                    break;
                default:
                    continue;
            }

            $filenames[] = $pageelement_name;
            $file_contents = $this->read_file("{$path}/{$entry}");

            $qb = $this->get_leaf_qb($page->id);
            $qb->add_constraint('name', '=', $pageelement_name);
            if ($qb->count() == 0) {
                // New element
                $pageelement = new midcom_db_pageelement();
                $pageelement->page = $page->id;
                $pageelement->name = $pageelement_name;
                $pageelement->$field = $file_contents;
                $pageelement->create();
                continue;
            }

            $pageelements = $qb->execute();
            $pageelement = $pageelements[0];

            // Update existing elements only if they have actually changed
            if ($pageelement->$field != $file_contents) {
                $pageelement->$field = $file_contents;
                $pageelement->update();
            }
        }
        $directory->close();

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $page->id);
            $this->delete_missing_files($filenames, $page->id);
        }
    }

    public function get_leaf_qb($parent_id)
    {
        $qb = midcom_db_pageelement::new_query_builder();
        $qb->add_constraint('page', '=', $parent_id);
        return $qb;
    }

    public function get_node_qb($parent_id)
    {
        $qb = midcom_db_page::new_query_builder();
        $qb->add_constraint('up', '=', $parent_id);
        return $qb;
    }

    public function import()
    {
        $nodes = $this->_read_dirs($this->root_dir);
        foreach ($nodes as $node) {
            $this->read_page($node, 0);
        }
    }
}
