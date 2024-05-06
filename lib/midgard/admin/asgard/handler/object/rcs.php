<?php
/**
 * @author tarjei huse
 * @package midgard.admin.asgard
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;
use midgard\portable\api\error\exception as mgd_exception;

/**
 * Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_rcs extends midcom_services_rcs_handler
{
    use midgard_admin_asgard_handler;

    protected string $style_prefix = 'midgard_admin_asgard_rcs_';

    protected string $url_prefix = '__mfa/asgard/object/rcs/';

    protected function load_object(string $guid) : midcom_core_dbaobject
    {
        try {
            return parent::load_object($guid);
        } catch (midcom_error_midgard $e) {
            $mgd_exception = $e->getPrevious();
            if (   $mgd_exception
                && $mgd_exception->getCode() == mgd_exception::OBJECT_DELETED) {
                return $this->load_deleted($guid);
            }
            throw $e;
        }
    }

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
     * Load l10n
     */
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', class: 'midgard_admin_asgard_plugin');
        $this->_component = 'midcom.admin.rcs';
        $this->_request_data['l10n'] = $this->_i18n->get_l10n('midcom.admin.rcs');
    }
}
