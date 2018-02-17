<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Export a site snippet
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_exporter_snippet extends midcom_helper_filesync_exporter
{
    private function read_snippetdir($snippetdir, $path)
    {
        $snippetdir_path = "{$path}{$snippetdir->name}";
        if (!file_exists($snippetdir_path)) {
            mkdir($snippetdir_path);
        }

        $filenames = [];
        $foldernames = [];

        $snippet_qb = midcom_db_snippet::new_query_builder();
        $snippet_qb->add_constraint('snippetdir', '=', $snippetdir->id);
        foreach ($snippet_qb->execute() as $snippet) {
            file_put_contents("{$snippetdir_path}/{$snippet->name}.php", $snippet->code);
            $filenames[] = "{$snippet->name}.php";

            if (!empty($snippet->doc)) {
                file_put_contents("{$snippetdir_path}/{$snippet->name}.txt", $snippet->doc);
                $filenames[] = "{$snippet->name}.txt";
            }
        }

        $dir_qb = midcom_db_snippetdir::new_query_builder();
        $dir_qb->add_constraint('up', '=', $snippetdir->id);
        foreach ($dir_qb->execute() as $dir) {
            $this->read_snippetdir($dir, "{$snippetdir_path}/");
            $foldernames[] = $dir->name;
        }

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $path);
            $this->delete_missing_files($filenames, $path);
        }
    }

    public function read_root($id)
    {
        if (is_numeric($id)) {
            $id = (int)$id;
        }
        $rootdir = new midcom_db_snippetdir($id);
        if (!$rootdir->can_do('midgard:update')) {
            return false;
        }
        $this->read_snippetdir($rootdir, $this->root_dir);
        return true;
    }

    public function export()
    {
        $qb = midcom_db_snippetdir::new_query_builder();
        $qb->add_constraint('up', '=', 0);
        foreach ($qb->execute() as $rootdir) {
            if ($rootdir->can_do('midgard:update')) {
                $this->read_snippetdir($rootdir, $this->root_dir);
            }
        }
    }
}
