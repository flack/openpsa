<?php
/**
 * @author tarjei huse
 * @package midgard.admin.asgard
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * @todo add support for schemas.
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_rcs extends midcom_services_rcs_handler
{
    protected $style_prefix = 'midgard_admin_asgard_rcs_';

    protected $url_prefix = '__mfa/asgard/object/rcs/';

    protected function get_object_url()
    {
        return "__mfa/asgard/object/open/{$this->object->guid}/";
    }

    protected function get_breadcrumbs()
    {
        midgard_admin_asgard_plugin::bind_to_object($this->object, $this->_request_data['handler_id'], $this->_request_data);
        return midcom_core_context::get()->get_custom_key('midcom.helper.nav.breadcrumb');
    }

    protected function handler_callback($handler_id)
    {
        $parts = explode('_', $handler_id);
        $mode = end($parts);
        return new midgard_admin_asgard_response($this, '_show_' . $mode);
    }

    /**
     * Load statics
     */
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
    }
}
