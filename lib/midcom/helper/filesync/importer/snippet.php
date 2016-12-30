<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Import a site snippet from the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_importer_snippet extends midcom_helper_filesync_importer
{
    private function read_snippetdir($path, $parent_id)
    {
        $snippetdir = $this->_get_node('midcom_db_snippetdir', $parent_id, $path);

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
                $this->read_snippetdir("{$path}/{$entry}", $snippetdir->id);
                $foldernames[] = $entry;
                continue;
            }

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2) {
                continue;
            }
            $snippet_name = $filename_parts[0];

            $field = false;
            switch ($filename_parts[count($filename_parts) - 1]) {
                case 'php':
                    $field = 'code';
                    break;
                case 'txt':
                    $field = 'doc';
                    break;
                default:
                    continue;
            }

            $filenames[] = $snippet_name;
            $file_contents = $this->read_file("{$path}/{$entry}");

            $qb = $this->get_leaf_qb($snippetdir->id);
            $qb->add_constraint('name', '=', $snippet_name);
            if ($qb->count() == 0) {
                // New element
                $snippet = new midcom_db_snippet();
                $snippet->snippetdir = $snippetdir->id;
                $snippet->name = $snippet_name;
                $snippet->$field = $file_contents;
                $snippet->create();
                continue;
            }

            $snippets = $qb->execute();
            $snippet = $snippets[0];

            // Update existing elements only if they have actually changed
            if ($snippet->$field != $file_contents) {
                $snippet->$field = $file_contents;
                $snippet->update();
            }
        }
        $directory->close();

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $snippetdir->id);
            $this->delete_missing_files($filenames, $snippetdir->id);
        }
    }

    public function get_leaf_qb($parent_id)
    {
        $qb = midcom_db_snippet::new_query_builder();
        $qb->add_constraint('snippetdir', '=', $parent_id);
        return $qb;
    }

    public function get_node_qb($parent_id)
    {
        $qb = midcom_db_snippetdir::new_query_builder();
        $qb->add_constraint('up', '=', $parent_id);
        return $qb;
    }

    public function import()
    {
        $nodes = $this->_read_dirs($this->root_dir);
        foreach ($nodes as $node) {
            $this->read_snippetdir($node, 0);
        }
    }
}
