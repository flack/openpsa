<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Import a site structure from the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_importer_structure extends midcom_helper_filesync_importer
{
    var $root_dir = '';

    public function __construct($delete_missing = false)
    {
        parent::__construct($delete_missing);

        $this->root_dir = midcom_helper_filesync_interface::prepare_dir('structure');
    }

    private function read_structure($structure, $parent_id = 0)
    {
        if ($parent_id == 0)
        {
            $topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC);
        }
        else
        {
            $object_qb = midcom_db_topic::new_query_builder();
            $object_qb->add_constraint('up', '=', $parent_id);
            $object_qb->add_constraint('name', '=', $structure['name']);
            if ($object_qb->count() == 0)
            {
                // New style
                $topic = new midcom_db_topic();
                $topic->up = $parent_id;
                $topic->name = $structure['name'];
                $topic->extra = $structure['title'];
                $topic->title = $structure['title'];
                $topic->component = $structure['component'];
                if (!$topic->create())
                {
                    return false;
                }

                if ($structure['create_index'])
                {
                    // Create index article for n.n.static
                    $article = new midcom_db_article();
                    $article->name = 'index';
                    $article->title = $structure['title'];
                    $article->topic = $topic->id;
                    $article->create();
                }
            }
            else
            {
                $topics = $object_qb->execute();
                $topic = $topics[0];
            }
        }

        // Update this folder properly
        $updated = false;

        if ($topic->extra != $structure['title'])
        {
            $topic->extra = $structure['title'];
            $topic->title = $structure['title'];
            $updated = true;
        }

        if ($topic->component != $structure['component'])
        {
            $topic->component = $structure['component'];
            $updated = true;
        }

        if ($topic->style != $structure['style'])
        {
            $topic->style = $structure['style'];
            $updated = true;
        }

        if ($topic->styleInherit != $structure['style_inherit'])
        {
            $topic->styleInherit = $structure['style_inherit'];
            $updated = true;
        }

        if ($updated)
        {
            $topic->update();
        }

        // Remove parameters that are not in the topic
        $existing_params = $topic->list_parameters();
        foreach ($existing_params as $domain => $params)
        {
            foreach ($params as $name => $value)
            {
                if (   !isset($structure['parameters'][$domain])
                    || !isset($structure['parameters'][$domain][$name]))
                {
                    $topic->set_parameter($domain, $name, '');
                }
            }
        }

        // Set all new parameters
        foreach ($structure['parameters'] as $domain => $params)
        {
            foreach ($params as $name => $value)
            {
                $topic->set_parameter($domain, $name, $value);
            }
        }

        // FIXME: Implement ACLs

        // Handle subdirectories
        $foldernames = array();
        foreach ($structure['nodes'] as $child_structure)
        {
            $this->read_structure($child_structure, $topic->id);
            $foldernames[] = $child_structure['name'];
        }

        if ($this->delete_missing)
        {
            // Then delete files and folders that are in DB but not in the importing folder
            $this->delete_missing_folders($foldernames, $topic->id);
        }
    }

    private function delete_missing_folders($foldernames, $topic_id)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $topic_id);

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

    public function import()
    {
        // Generate a safe name for the structure
        $structure_name = midcom_helper_misc::generate_urlname_from_string(midcom::get()->get_page_prefix());
        $path = "{$this->root_dir}{$structure_name}.inc";

        if (!file_exists($path))
        {
            throw new midcom_error("Structure file {$path} not found");
            // This will exit.
        }

        $structuredata = file_get_contents($path);
        $structure = midcom_helper_misc::parse_config($structuredata);

        if (   !is_array($structure)
            || !isset($structure[$structure_name])
            || !isset($structure[$structure_name]['root']))
        {
            throw new midcom_error("Invalid structure file {$path}");
            // This will exit.
        }

        $this->read_structure($structure[$structure_name]['root']);
    }
}
?>