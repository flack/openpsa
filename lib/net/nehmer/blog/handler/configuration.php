<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * n.n.blog component configuration screen.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_configuration extends midcom_baseclasses_components_handler_configuration_recreate
{
    public function _load_datamanagers()
    {
        return [
            'midcom_db_article' => new datamanager($this->_request_data['schemadb'])
        ];
    }

    public function _load_objects()
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_request_data['topic']->id);
        return $qb->execute();
    }
}
