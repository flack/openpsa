<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for objects
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_link_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nehmer_blog_link';

    /**
     * Check if all the fields contain required information upon update
     *
     * @return boolean Indicating success
     */
    public function _on_updating()
    {
        if (   !$this->topic
            || !$this->article) {
            debug_add('Failed to update the link, either topic or article was undefined', MIDCOM_LOG_WARN);
            midcom_connection::set_error(MGD_ERR_ERROR);
            return false;
        }

        return true;
    }
}
