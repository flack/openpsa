<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Export pages to the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_exporter_page extends midcom_helper_filesync_exporter
{
    private function read_page($page, $path)
    {
        $page_path = "{$path}{$page->name}";
        if (!file_exists($page_path)) {
            mkdir($page_path);
        }

        $filenames = array();
        $foldernames = array();

        $pageelement_qb = midcom_db_pageelement::new_query_builder();
        $pageelement_qb->add_constraint('page', '=', $page->id);
        $pageelements = $pageelement_qb->execute();
        foreach ($pageelements as $pageelement) {
            file_put_contents("{$page_path}/{$pageelement->name}.php", $pageelement->value);
            $filenames[] = "{$pageelement->name}.php";
        }

        $dir_qb = midcom_db_page::new_query_builder();
        $dir_qb->add_constraint('up', '=', $page->id);
        $dirs = $dir_qb->execute();
        foreach ($dirs as $dir) {
            $this->read_page($dir, "{$page_path}/");
            $foldernames[] = $dir->name;
        }

        if ($this->delete_missing) {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $path);
            $this->delete_missing_files($filenames, $path);
        }
    }

    /**
     * Export a page tree
     *
     * @param integer $id Page ID
     * @return boolean Indicating success
     */
    public function read_root($id)
    {
        if (is_numeric($id)) {
            $id = (int)$id;
        }
        $rootdir = new midcom_db_page($id);
        if (!$rootdir->can_do('midgard:update')) {
            return false;
        }
        $this->read_page($rootdir, $this->root_dir);
        return true;
    }

    public function export()
    {
        $qb = midcom_db_page::new_query_builder();
        $qb->add_constraint('up', '=', 0);
        $rootdirs = $qb->execute();
        foreach ($rootdirs as $rootdir) {
            if ($rootdir->can_do('midgard:update')) {
                $this->read_page($rootdir, $this->root_dir);
            }
        }
    }
}
