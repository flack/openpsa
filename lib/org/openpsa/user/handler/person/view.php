<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * View person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_view extends midcom_baseclasses_components_handler
{
    public function _handler_view(string $guid, array &$data)
    {
        $person = new midcom_db_person($guid);
        $data['account'] = new midcom_core_account($person);
        $data['view'] = datamanager::from_schemadb($this->_config->get('schemadb_person'))
            ->set_storage($person);

        $this->add_breadcrumb('', $person->get_label());

        $auth = midcom::get()->auth;
        if (   $person->guid == midcom::get()->auth->user->guid
            || $auth->can_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class)) {
            $buttons = [];
            $workflow = $this->get_workflow('datamanager');
            if ($person->can_do('midgard:update')) {
                $buttons[] = $workflow->get_button($this->router->generate('user_edit', ['guid' => $person->guid]), [
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                ]);
            }
            if ($person->can_do('midgard:delete')) {
                $delete_workflow = $this->get_workflow('delete', ['object' => $person]);
                $buttons[] = $delete_workflow->get_button($this->router->generate('user_delete', ['guid' => $person->guid]));
            }
            if (   $data['account']->get_username()
                && $person->can_do('midgard:privileges')) {
                $buttons[] = $workflow->get_button($this->router->generate('user_privileges', ['guid' => $person->guid]), [
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("permissions"),
                    MIDCOM_TOOLBAR_GLYPHICON => 'shield',
                ]);
            }

            if ($person->can_do('midgard:update') && midcom::get()->componentloader->is_installed('org.openpsa.notifications')) {
                $buttons[] = $workflow->get_button($this->router->generate('person_notifications', ['guid' => $person->guid]), [
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("notification settings"),
                    MIDCOM_TOOLBAR_GLYPHICON => 'bell-o',
                ]);
            }
            $this->_view_toolbar->add_items($buttons);
        }
        $this->bind_view_to_object($person);

        $data['person'] = $person;
        return $this->show('show-person');
    }
}
