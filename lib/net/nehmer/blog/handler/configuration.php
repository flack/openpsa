<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.blog component configuration screen.
 *
 * This class extends the standard configdm2 mechanism as we need a few hooks for the
 * thumbnail regeneration.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_configuration extends midcom_baseclasses_components_handler_configuration
{
    function _load_datamanagers()
    {
        return array(
            'midcom_db_article' => new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb'])
        );
    }

    function _load_objects()
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_request_data['topic']->id);
        return $qb->execute();
    }
}
