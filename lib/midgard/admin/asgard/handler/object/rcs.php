<?php
/**
 * @author tarjei huse
 * @package midgard.admin.asgard
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

/**
 * Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_rcs extends midcom_services_rcs_handler
{
    use midgard_admin_asgard_handler;

    protected $style_prefix = 'midgard_admin_asgard_rcs_';

    protected $url_prefix = '__mfa/asgard/object/rcs/';

    protected function get_object_url() : string
    {
        return $this->router->generate('object_open', ['guid' => $this->object->guid]);
    }

    protected function get_breadcrumbs() : array
    {
        midgard_admin_asgard_plugin::bind_to_object($this->object, $this->_request_data['handler_id'], $this->_request_data);
        return midcom_core_context::get()->get_custom_key('midcom.helper.nav.breadcrumb') ?: [];
    }

    protected function reply(string $element) : Response
    {
        return $this->get_response($element);
    }

    /**
     * Load statics and l10n
     */
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $this->_l10n = $this->_i18n->get_l10n('midcom.admin.rcs');
        $this->_request_data['l10n'] = $this->_l10n;
    }
}
