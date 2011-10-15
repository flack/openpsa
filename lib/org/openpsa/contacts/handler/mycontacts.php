<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * My Contacts handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_mycontacts extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_add($handler_id, array $args, array &$data)
    {
        $target = org_openpsa_contacts_person_dba::get_cached($args[0]);

        $mycontacts = new org_openpsa_contacts_mycontacts;
        $mycontacts->add($args[0]);

        $_MIDCOM->relocate("person/{$target->guid}/");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_remove($handler_id, array $args, array &$data)
    {
        $target = org_openpsa_contacts_person_dba::get_cached($args[0]);

        $mycontacts = new org_openpsa_contacts_mycontacts;
        $mycontacts->remove($args[0]);

        $_MIDCOM->relocate("person/{$target->guid}/");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $_MIDCOM->skip_page_style = true;

        if ($handler_id == 'mycontacts_xml')
        {
            $_MIDCOM->auth->require_valid_user('basic');
            $_MIDCOM->cache->content->content_type("text/xml; charset=UTF-8");
            $_MIDCOM->header("Content-type: text/xml; charset=UTF-8");
        }
        else
        {
            $_MIDCOM->auth->require_valid_user();
        }

        $mycontacts = new org_openpsa_contacts_mycontacts;
        $data['mycontacts'] = $mycontacts->list_members();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        if (count($data['mycontacts']) > 0)
        {
            if ($handler_id == 'mycontacts_xml')
            {
                $schemadb_person = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));

                $datamanager = new midcom_helper_datamanager2_datamanager($schemadb_person);
                $xml = '<contacts></contacts>';
                $simplexml = simplexml_load_string($xml);

                foreach ($data['mycontacts'] as $person)
                {
                    $contact = $simplexml->addChild('contact');
                    $contact->addAttribute('guid', $person->guid);
                    $datamanager->autoset_storage($person);
                    $person_data = $datamanager->get_content_xml();

                    foreach ($person_data as $key => $value)
                    {
                        $contact->addChild($key, $value);
                    }

                    $mc = midcom_db_member::new_collector('uid', $person->id);
                    $memberships = $mc->get_values('gid');
                    $qb = org_openpsa_contacts_group_dba::new_query_builder();
                    $qb->add_constraint('gid', 'IN', $memberships);
                    $qb->add_constraint('orgOpenpsaObtype', '>', org_openpsa_contacts_list_dba::MYCONTACTS);
                    $organisations = $qb->execute();

                    foreach ($organisations as $organisation)
                    {
                        $contact->addChild('company', str_replace('&', '&amp;', $$organisation->get_label()));
                    }
                }

                echo $simplexml->asXml();
            }
            else
            {
                midcom_show_style("show-mycontacts-header");
                foreach ($data['mycontacts'] as $person)
                {
                    $data['person'] = $person;
                    midcom_show_style("show-mycontacts-item");
                }
                midcom_show_style("show-mycontacts-footer");
            }
        }
    }
}
?>