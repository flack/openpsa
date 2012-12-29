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
                $this->read_style("{$path}/{$entry}", $style->id);
                $foldernames[] = $entry;
            }

            // Deal with element

            // Check file type
            $filename_parts = explode('.', $entry);
            if (count($filename_parts) < 2)
            {
                continue;
            }
            $element_name = $filename_parts[0];
            $field = false;
            switch($filename_parts[count($filename_parts) - 1])
            {
                case 'php':
                    $field = 'value';
                    break;
            }
            if (!$field)
            {
                continue;
            }

            $filenames[] = $element_name;

            $file_contents = file_get_contents("{$path}/{$entry}");
            $encoding = mb_detect_encoding($file_contents);
            if ($encoding != 'UTF-8')
            {
                $file_contents = @iconv($encoding, 'UTF-8', $file_contents);
            }

            $qb = midcom_db_element::new_query_builder();
            $qb->add_constraint('style', '=', $style->id);
            $qb->add_constraint('name', '=', $element_name);
            if ($qb->count() == 0)
            {
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
            if ($element->$field != $file_contents)
            {
                $element->$field = $file_contents;
                $element->update();
            }
        }
        $directory->close();

        if ($this->delete_missing)
        {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $style->id);
            $this->delete_missing_files($filenames, $style->id);
        }
    }

    private function read_styledir($path)
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
                $this->read_style("{$path}/{$entry}", 0);
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

    private function delete_missing_folders($foldernames, $style_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_style::new_query_builder();
        $qb->add_constraint('up', '=', $style_id);

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

    private function delete_missing_files($filenames, $style_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_element::new_query_builder();
        $qb->add_constraint('style', '=', $style_id);

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
        $this->read_styledir($this->root_dir);
    }
}
?>