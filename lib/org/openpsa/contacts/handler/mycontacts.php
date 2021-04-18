<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\helper\autocomplete;
use Symfony\Component\HttpFoundation\Request;

/**
 * My Contacts handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_mycontacts extends midcom_baseclasses_components_handler
{
    public function _handler_add(Request $request, string $guid)
    {
        $target = org_openpsa_contacts_person_dba::get_cached($guid);

        $mycontacts = new org_openpsa_contacts_mycontacts;
        $mycontacts->add($target->guid);

        $return_url = $request->query->get('return_url', $this->router->generate('person_view', ['guid' => $guid]));
        return new midcom_response_relocate($return_url);
    }

    public function _handler_remove(string $guid)
    {
        $target = org_openpsa_contacts_person_dba::get_cached($guid);

        $mycontacts = new org_openpsa_contacts_mycontacts;
        $mycontacts->remove($target->guid);

        return new midcom_response_relocate($this->router->generate('person_view', ['guid' => $target->guid]));
    }

    public function _handler_list(array &$data)
    {
        $data['widget_config'] = autocomplete::get_widget_config('contact');
        $data['widget_config']['id_field'] = 'guid';

        autocomplete::add_head_elements();
    }

    public function _show_list(string $handler_id, array &$data)
    {
        midcom_show_style('show-mycontacts-header');
        $mycontacts = new org_openpsa_contacts_mycontacts;
        foreach ($mycontacts->list_members() as $person) {
            $data['person'] = $person;
            midcom_show_style('show-mycontacts-item');
        }
        midcom_show_style('show-mycontacts-footer');
    }
}
