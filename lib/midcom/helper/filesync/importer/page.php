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
    var $root_dir = '';

    public function __construct($delete_missing = false)
    {
        parent::__construct($delete_missing);
        $this->root_dir = midcom_helper_filesync_interface::prepare_dir('pages');
    }

    private function read_page($path, $parent_id)
    {
        $page_name = basename($path);

        $object_qb = midcom_db_page::new_query_builder();
        $object_qb->add_constraint('up', '=', $parent_id);
        $object_qb->add_constraint('name', '=', $page_name);
        if ($object_qb->count() == 0)
        {
            // New page
            $page = new midcom_db_page();
            $page->up = $parent_id;
            $page->name = $page_name;
            if (!$page->create())
            {
                return false;
            }
        }
        else
        {
            $pages = $object_qb->execute();
            $page = $pages[0];
        }

        $directory = dir($path);
        $foldernames = array();
        $filenames = array();
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // Recurse deeper
                $this->read_page("{$path}/{$entry}", $page->id);
                $foldernames[] = $entry;
                continue;
            }

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2)
            {
                continue;
            }
            $pageelement_name = $filename_parts[0];

            $field = false;
            switch ($filename_parts[count($filename_parts) - 1])
            {
                case 'php':
                    $field = 'value';
                    break;
            }
            
            if (!$field)
            {
                continue;
            }
            
            $filenames[] = $pageelement_name;

            // Deal with element
            $file_contents = file_get_contents("{$path}/{$entry}");
            $encoding = mb_detect_encoding($file_contents);
            if ($encoding != 'UTF-8')
            {
                $file_contents = @iconv($encoding, 'UTF-8', $file_contents);
            }

            $qb = midcom_db_pageelement::new_query_builder();
            $qb->add_constraint('page', '=', $page->id);
            $qb->add_constraint('name', '=', $pageelement_name);
            if ($qb->count() == 0)
            {
                // New element
                $pageelement = new midcom_db_pageelement();
                $pageelement->page = $page->id;
                $pageelement->name = $pageelement_name;
                $pageelement->$field = $file_contents;
                $pageelement->create();
                continue;
            }

            $qb->toggle_read_only(false);

            $pageelements = $qb->execute();
            $pageelement = $pageelements[0];

            // Update existing elements only if they have actually changed
            if ($pageelement->$field != $file_contents)
            {
                $pageelement->$field = $file_contents;
                $pageelement->update();
            }
        }
        $directory->close();

        if ($this->delete_missing)
        {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $page->id);
            $this->delete_missing_files($filenames, $page->id);
        }
    }

    private function read_dirs($path)
    {
        $directory = dir($path);
        $foldernames = array();
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // Recurse deeper
                $this->read_page("{$path}/{$entry}", 0);
                $foldernames[] = $entry;
            }
        }
        $directory->close();

        if ($this->delete_missing)
        {
            // Then delete files and folders that are in DB but not in the importing folder 
            $this->delete_missing_folders($foldernames, 0);
        }
    }

    private function delete_missing_folders($foldernames, $page_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_page::new_query_builder();
        $qb->add_constraint('up', '=', $page_id);

        if (!empty($foldernames))
        {
            $qb->add_constraint('name', 'NOT IN', $foldernames);
        }
        $folders = $qb->execute();
        foreach ($folders as $folder)
        {
            $folder->delete();
        }
    }

    private function delete_missing_files($filenames, $page_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_pageelement::new_query_builder();
        $qb->add_constraint('up', '=', $page_id);

        if (!empty($filenames))
        {
            $qb->add_constraint('name', 'NOT IN', $filenames);
        }

        $files = $qb->execute();
        foreach ($files as $file)
        {
            $file->delete();
        }
    }

    public function import()
    {
        $this->read_dirs($this->root_dir);
    }
}
?>