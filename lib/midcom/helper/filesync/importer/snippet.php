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
                $this->read_snippetdir("{$path}/{$entry}", $snippetdir->id);
                $foldernames[] = $entry;
                continue;
            }

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2)
            {
                continue;
            }
            $snippet_name = $filename_parts[0];

            $field = false;
            switch ($filename_parts[count($filename_parts) - 1])
            {
                case 'php':
                    $field = 'code';
                    break;
                case 'txt':
                    $field = 'doc';
                    break;
            }
            if (!$field)
            {
                continue;
            }

            $filenames[] = $snippet_name;

            // Deal with element
            $file_contents = file_get_contents("{$path}/{$entry}");
            $encoding = mb_detect_encoding($file_contents);
            if ($encoding != 'UTF-8')
            {
                $file_contents = @iconv($encoding, 'UTF-8', $file_contents);
            }

            $qb = midcom_db_snippet::new_query_builder();
            $qb->add_constraint('up', '=', $snippetdir->id);
            $qb->add_constraint('name', '=', $snippet_name);
            if ($qb->count() == 0)
            {
                // New element
                $snippet = new midcom_db_snippet();
                $snippet->up = $snippetdir->id;
                $snippet->name = $snippet_name;
                $snippet->$field = $file_contents;
                $snippet->create();
                continue;
            }

            $snippets = $qb->execute();
            $snippet = $snippets[0];

            // Update existing elements only if they have actually changed
            if ($snippet->$field != $file_contents)
            {
                $snippet->$field = $file_contents;
                $snippet->update();
            }
        }
        $directory->close();

        if ($this->delete_missing)
        {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $snippetdir->id);
            $this->delete_missing_files($filenames, $snippetdir->id);
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
                $this->read_snippetdir("{$path}/{$entry}", 0);
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

    private function delete_missing_folders($foldernames, $snippetdir_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_snippetdir::new_query_builder();
        $qb->add_constraint('up', '=', $snippetdir_id);

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

    private function delete_missing_files($filenames, $snippetdir_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_snippet::new_query_builder();
        $qb->add_constraint('up', '=', $snippetdir_id);

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