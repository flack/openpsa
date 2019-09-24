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
     * Shows the navigation tree
     *
     * @param array $tree_array - array which contains tree (built by _tree_array_build)
     * @param string $link_url - contains the link for the item
     */
    private function _show_navigation_tree(array $tree_array, string $link_url)
    {
        foreach (array_filter($tree_array, 'is_array') as $tree) {
            $this->_request_data["name"] = $tree["topic"]->extra;
            $this->_request_data["id"] = $tree["topic"]->id;
            $this->_request_data["link_url"] = $link_url . "/" . $tree["topic"]->name . '/';
            midcom_show_style('show-navigation-item-begin');

            if (count($tree) > 1) {
                midcom_show_style('show-navigation-submenu-begin');
                $this->_show_navigation_tree($tree, $link_url . "/" . $tree["topic"]->name);
                midcom_show_style('show-navigation-submenu-end');
            }
            midcom_show_style('show-navigation-item-end');
        }
    }

    /**
     * Builds an array with a tree structure from a given array of topics
     *
     * @param array $topic_array - array which contains the topics for the tree
     * @param midcom_db_topic $root_topic - root topic of the tree
     * @param array $tree_array - array which contains the tree
     */
    private function _tree_array_build(array $topic_array, midcom_db_topic $root_topic, array &$tree_array)
    {
        foreach ($topic_array as $topic) {
            if ($topic->up == $root_topic->id) {
                $tree_array[$root_topic->id][$topic->id] = ["topic" => $topic];
                $this->_tree_array_build($topic_array, $topic, $tree_array[$root_topic->id]);
            }
        }
    }

    public function _handler_navigation(array &$data)
    {
        $root_topic = $this->_topic;
        while ($root_topic->up && $root_topic->get_parent()->component == $this->_component) {
            $root_topic = $root_topic->get_parent();
        }
        $data['root_topic'] = $root_topic;

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint("component", "=", $this->_component);
        $qb->add_constraint("up", "INTREE", $root_topic->id);
        $qb->add_order('extra');
        $data['topic_array'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_navigation($handler_id, array &$data)
    {
        $tree_array = [
            $data['root_topic']->id => ['topic' => $data['root_topic']]
        ];
        $this->_tree_array_build($data['topic_array'], $data['root_topic'], $tree_array);

        midcom_show_style('show-navigation-start');
        $this->_show_navigation_tree($tree_array, midcom_connection::get_url('prefix'));
        midcom_show_style('show-navigation-end');
    }
}
