<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * OpenPSA Contact registers/user manager
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * Prepares the component's indexer client
     */
    public function _on_reindex($topic, midcom_helper_configuration $config, midcom_services_indexer $indexer)
    {
        $qb_organisations = org_openpsa_contacts_group_dba::new_query_builder();
        $organisation_dm = datamanager::from_schemadb($config->get('schemadb_group'));

        $qb_persons = org_openpsa_contacts_person_dba::new_query_builder();
        $person_dm = datamanager::from_schemadb($config->get('schemadb_person'));

        $indexer = new org_openpsa_contacts_midcom_indexer($topic, $indexer);
        $indexer->add_query('organisations', $qb_organisations, $organisation_dm);
        $indexer->add_query('persons', $qb_persons, $person_dm);

        return $indexer;
    }

    /**
     * Locates the root group
     */
    public static function find_root_group() : midcom_db_group
    {
        static $root_group;

        //Check if we have already initialized
        if (!empty($root_group)) {
            return $root_group;
        }

        $qb = midcom_db_group::new_query_builder();
        $qb->add_constraint('owner', '=', 0);
        $qb->add_constraint('name', '=', '__org_openpsa_contacts');

        if ($results = $qb->execute()) {
            return $root_group = end($results);
        }

        debug_add("OpenPSA Contacts root group could not be found", MIDCOM_LOG_WARN);

        //Attempt to  auto-initialize the group.
        $root_group = new midcom_db_group();
        $root_group->owner = 0;
        $root_group->name = '__org_openpsa_contacts';
        $root_group->official = midcom::get()->i18n->get_string($root_group->name, 'org.openpsa.contacts');
        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $ret = $root_group->create();
        midcom::get()->auth->drop_sudo();
        if (!$ret) {
            throw new midcom_error("Could not auto-initialize the module, group creation failed: " . midcom_connection::get_error_string());
        }
        return $root_group;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if (   $object instanceof org_openpsa_contacts_group_dba
            || $object instanceof midcom_db_group) {
            return "group/{$object->guid}/";
        }
        if (   $object instanceof org_openpsa_contacts_person_dba
            || $object instanceof midcom_db_person) {
            return "person/{$object->guid}/";
        }
        return null;
    }
}
