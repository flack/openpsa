<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Buddy list handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_buddy_list extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_add($handler_id, $args, &$data)
    {
        $user =& $_MIDCOM->auth->user->get_storage();
        $user->require_do('midgard:create');

        $target = new org_openpsa_contacts_person_dba($args[0]);
        if (!$target)
        {
            return false;
        }

        // Check we're not buddies already
        $qb = org_openpsa_contacts_buddy_dba::new_query_builder();
        $qb->add_constraint('account', '=', $user->guid);
        $qb->add_constraint('buddy', '=', $target->guid);
        $qb->add_constraint('isapproved', '=', true);
        $buddies = $qb->execute();
        if (count($buddies) > 0)
        {
            return false;
        }

        $buddy = new org_openpsa_contacts_buddy_dba();
        $buddy->account = $user->guid;
        $buddy->buddy = $target->guid;
        $buddy->isapproved = true;
        if (!$buddy->create())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to add buddy, reason " . midcom_connection::get_error_string());
            // This will exit
        }

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $_MIDCOM->relocate("{$prefix}person/{$target->guid}/");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_remove($handler_id, $args, &$data)
    {
        $user =& $_MIDCOM->auth->user->get_storage();
        $user->require_do('midgard:create');

        $target = new org_openpsa_contacts_person_dba($args[0]);
        if (!$target)
        {
            return false;
        }

        // Check we're not buddies already
        $qb = org_openpsa_contacts_buddy_dba::new_query_builder();
        $qb->add_constraint('account', '=', $user->guid);
        $qb->add_constraint('buddy', '=', $target->guid);
        $buddies = $qb->execute();
        if (count($buddies) == 0)
        {
            return false;
        }

        foreach ($buddies as $buddy)
        {
            if (!$buddy->delete())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to add buddy, reason " . midcom_connection::get_error_string());
                // This will exit
            }
        }

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $_MIDCOM->relocate("{$prefix}person/{$target->guid}/");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->skip_page_style = true;

        if ($handler_id == 'buddylist_xml')
        {
            $_MIDCOM->auth->require_valid_user('basic');
            $_MIDCOM->cache->content->content_type("text/xml; charset=UTF-8");
            $_MIDCOM->header("Content-type: text/xml; charset=UTF-8");
        }
        else
        {
            $_MIDCOM->auth->require_valid_user();
        }

        $user = $_MIDCOM->auth->user->get_storage();

        $this->_request_data['buddylist'] = array();

        $qb = org_openpsa_contacts_buddy_dba::new_query_builder();
        $qb->add_constraint('account', '=', $user->guid);
        $qb->add_constraint('blacklisted', '=', false);
        $qb->add_order('buddy.lastname');
        $qb->add_order('buddy.firstname');
        $buddies = $qb->execute();

        foreach ($buddies as $buddy)
        {
            $person = new org_openpsa_contacts_person_dba($buddy->buddy);
            if (   $person
                && $person->guid)
            {
                $this->_request_data['buddylist'][] = $person;
            }
        }
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        if (count($this->_request_data['buddylist']) > 0)
        {
            if ($handler_id == 'buddylist_xml')
            {
                $_MIDCOM->load_library('midcom.helper.datamanager2');
                $schemadb_person = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));


                $datamanager = new midcom_helper_datamanager2_datamanager($schemadb_person);
                $xml = '<buddies></buddies>';
                $simplexml = simplexml_load_string($xml);

                foreach ($data['buddylist'] as $person)
                {
                    $buddy = $simplexml->addChild('buddy');
                    $buddy->addAttribute('guid', $person->guid);
                    $datamanager->autoset_storage($person);
                    $person_data = $datamanager->get_content_xml();

                    foreach ($person_data as $key => $value)
                    {
                        $buddy->addChild($key, $value);
                    }

                    $qb = midcom_db_member::new_query_builder();
                    $qb->add_constraint('uid', '=', $person->id);
                    $memberships = $qb->execute();
                    foreach ($memberships as $membership)
                    {
                        $group = new org_openpsa_contacts_group_dba($membership->gid);
                        //$buddy->addChild('company', htmlentities($group->get_label(), ENT_NOQUOTES, 'UTF-8'));
                        $buddy->addChild('company', str_replace('&', '&amp;', $group->get_label()));
                        break;
                    }
                }

                echo $simplexml->asXml();
            }
            else
            {
                $_MIDCOM->load_library('org.openpsa.contactwidget');
                midcom_show_style("show-buddylist-header");
                foreach ($data['buddylist'] as $person)
                {
                    $data['person'] =& $person;
                    midcom_show_style("show-buddylist-item");
                }
                midcom_show_style("show-buddylist-footer");
            }
        }
    }
}
?>