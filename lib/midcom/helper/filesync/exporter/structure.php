<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Export a site structure to the format used by Site Wizard
 *
 * @package midcom.helper.filesync
 */
class midcom_helper_filesync_exporter_structure extends midcom_helper_filesync_exporter
{
    var $structure_array = array();
    var $structure = '';

    function read_node($node)
    {
        $node_array = array();
        $node_array['name'] = $node->name;
        $node_array['title'] = $node->extra;
        $node_array['component'] = $node->component;
        $node_array['style'] = $node->style;
        $node_array['style_inherit'] = $node->styleInherit;

        // Per-component specialties
        switch ($node->component)
        {
            case 'net.nehmer.static':
                $node_array['create_index'] = true;
        }

        // Get parameters
        $node_array['parameters'] = $node->list_parameters();

        // TODO: Implement ACL exporting
        $node_array['acl'] = array();

        // Recurse subnodes
        $node_array['nodes'] = array();
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $node->id);
        $children = $qb->execute();
        foreach ($children as $child)
        {
            $node_array['nodes'][$child->name] = $this->read_node($child);
        }

        return $node_array;
    }

    function read_structure()
    {
        // Generate a safe name for the structure
        $structure_name = midcom_helper_misc::generate_urlname_from_string(midcom::get()->get_page_prefix());

        // Prepare structure
        $structure = array();
        $structure[$structure_name] = array();
        $structure[$structure_name]['name'] = $structure_name;
        $structure[$structure_name]['title'] = $GLOBALS['midcom_config']['midcom_site_title'];
        // Read the topic data
        $root_node = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        $structure[$structure_name]['root'] = $this->read_node($root_node);

        file_put_contents("{$this->root_dir}{$structure_name}.inc", $this->_draw_array($structure));
    }

    function _draw_array($array, $prefix = '')
    {
        $data = '';
        foreach ($array as $key => $val)
        {
            $data .= $prefix;
            if (!is_numeric($key))
            {
                $data .= "'{$key}' => ";
            }

            switch (gettype($val))
            {
                case 'boolean':
                    $data .= ($val)?'true':'false';
                    break;
                case 'array':
                    if (empty($val))
                    {
                        $data .= 'array()';
                    }
                    else
                    {
                        $data .= "array\n{$prefix}(\n" . $this->_draw_array($val, "{$prefix}    ") . "{$prefix})";
                    }
                    break;

                default:
                    if (is_numeric($val))
                    {
                        $data .= $val;
                    }
                    else
                    {
                        $data .= "'" . str_replace("'", "\'", $val) . "'";
                    }
            }

            $data .= ",\n";

        }
        return $data;
    }

    function export()
    {
        $this->read_structure();
    }
}
?>