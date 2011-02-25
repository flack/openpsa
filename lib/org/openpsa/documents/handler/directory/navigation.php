<?php
/**
 * @package org.openpsa.documents
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_directory_navigation extends midcom_baseclasses_components_handler
{
    /**
     * shows the navigation tree
     *
     * @param Array $tree_array - array which contains tree (build by _tree_array_build)
     * @param Array $link_url - contains the link for the item
     * @param Array $current_topic -
     *
     */
    private function _show_navigation_tree($tree_array, $link_url = "")
    {
        foreach ($tree_array as $tree)
        {
            if (is_array($tree))
            {
                $this->_request_data["name"] = $tree["topic"]->extra;
                $this->_request_data["id"] = $tree["topic"]->id;
                $this->_request_data["link_url"] = $link_url . "/" . $tree["topic"]->name;
                midcom_show_style("show-navigation-item-begin");

                if(count($tree) > 1)
                {
                    midcom_show_style("show-navigation-submenu-begin");
                    $this->_show_navigation_tree($tree, $link_url . "/" . $tree["topic"]->name);
                    midcom_show_style("show-navigation-submenu-end");
                }
                midcom_show_style("show-navigation-item-end");
            }
        }
    }

    /**
     * builds an array with a tree-structure from a given array of topics
     *
     * @param Array $topic_array - array which contains the topics for the tree
     * @param Array $root_topic - root topic of the tree
     * @param Array &$tree:array - array which contains the tree
     *
     */
    private function _tree_array_build($topic_array, $root_topic, &$tree_array)
    {
        foreach ($topic_array as $topic)
        {
            if ($topic->up == $root_topic->id)
            {
                if(is_array($tree_array[$root_topic->id]))
                {
                    $tree_array[$root_topic->id][$topic->id] = array
                    (
                        "topic" => $topic
                    );
                }
                else
                {
                    $tree_array[$root_topic->id] = array
                    (
                        $topic->id => array
                        (
                            "topic" => $topic
                        ),
                        "topic" => $root_topic
                    );
                }
                $this->_tree_array_build($topic_array, $topic, $tree_array[$root_topic->id]);
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_navigation($handler_id, $args, &$data)
    {
        $current_topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC);
        $current_component = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC)->component;
        $root_topic = $current_topic;
        while ($root_topic->get_parent()->component == $current_component)
        {
            $root_topic = $root_topic->get_parent();
        }
        $this->_request_data['root_topic'] = $root_topic;
        $this->_request_data['current_topic'] = $current_topic;

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint("component", "=", $current_component);
        $qb->add_constraint("up", "INTREE", $root_topic->id);
        $document_topics = $qb->execute();
        $this->_request_data['topic_array'] = $document_topics;

        //This handler is supposed to be used with dynamic_load or AJAX, so skip page style
        $_MIDCOM->skip_page_style = true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_navigation($handler_id, &$data)
    {
        $tree_array = array
        (
            $this->_request_data['root_topic']->id => $this->_request_data['root_topic']
        );
        $this->_tree_array_build($this->_request_data['topic_array'], $this->_request_data['root_topic'], $tree_array);

        midcom_show_style("show-navigation-start");
        $this->_show_navigation_tree($tree_array, midcom_connection::get_url('prefix'));
        midcom_show_style("show-navigation-end");
    }
}
?>