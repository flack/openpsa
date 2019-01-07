<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Person creation class
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_user_create extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.user/usermgmt.css');
        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midgard.admin.user'), $this->_request_data);
    }

    /**
     * @param Request $request The request object
     * @param array $data Data passed to the show method
     */
    public function _handler_create(Request $request, array &$data)
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_person'));
        $person = new midcom_db_person;
        $dm->set_storage($person);
        $data['controller'] = $dm->get_controller();
        switch ($data['controller']->handle($request)) {
            case 'save':
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('person %s saved'), $person->name));
                return new midcom_response_relocate($this->router->generate('user_edit', ['guid' => $person->guid]));

            case 'cancel':
                return new midcom_response_relocate($this->router->generate('user_list'));
        }

        $data['view_title'] = $this->_l10n->get('create user');

        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('user_create'), $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_create');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $data Data passed to the show method
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style('midgard-admin-user-person-create');
    }
}
