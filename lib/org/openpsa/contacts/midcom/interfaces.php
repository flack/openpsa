<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA Contact registers/user manager
 *
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_interface extends midcom_baseclasses_components_interface
{
    /**
     * Initialize
     *
     * Initialize the basic data structures needed by the component
     */
    public function _on_initialize()
    {
        //org.openpsa.contacts object types
        define('ORG_OPENPSA_OBTYPE_OTHERGROUP', 0);
        define('ORG_OPENPSA_OBTYPE_MYCONTACTS', 500);
        define('ORG_OPENPSA_OBTYPE_ORGANIZATION', 1000);
        define('ORG_OPENPSA_OBTYPE_DAUGHTER', 1001);
        define('ORG_OPENPSA_OBTYPE_DEPARTMENT', 1002);
        define('ORG_OPENPSA_OBTYPE_PERSON', 2000);
        define('ORG_OPENPSA_OBTYPE_RESOURCE', 2001);
        return true;
    }

    /**
     * Iterate over all groups and create index record using the datamanager indexer
     * method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        $qb->add_constraint('orgOpenpsaObtype', '<>', 0);
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            $schema = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_group'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schema);

            foreach ($ret as $group)
            {
                if (!$datamanager->autoset_storage($group))
                {
                    debug_add("Warning, failed to initialize datamanager for group {$group->id}. See Debug Log for details.", MIDCOM_LOG_WARN);
                    debug_print_r('Group dump:', $group);

                    continue;
                }
                org_openpsa_contacts_viewer::index_group($datamanager, $indexer, $topic);
            }
        }

        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            $schema = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_person'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schema);
            if (!$datamanager)
            {
                debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $this->_config->get('schemadb_document'),
                    MIDCOM_LOG_WARN);
                return false;
            }

            foreach ($ret as $person)
            {
                if (!$datamanager->autoset_storage($person))
                {
                    debug_add("Warning, failed to initialize datamanager for person {$person->id}. See Debug Log for details.", MIDCOM_LOG_WARN);
                    debug_print_r('Person dump:', $person);

                    continue;
                }
                org_openpsa_contacts_viewer::index_person($datamanager, $indexer, $topic);
            }
        }

        return true;
    }

    /**
     * Locates the root group
     */
    static function find_root_group($name = '__org_openpsa_contacts')
    {
        static $root_groups = array();

        //Check if we have already initialized
        if (!empty($root_groups[$name]))
        {
            return $root_groups[$name];
        }

        $qb = midcom_db_group::new_query_builder();
        $qb->add_constraint('owner', '=', 0);
        $qb->add_constraint('name', '=', $name);

        $results = $qb->execute();

        if (   is_array($results)
            && count($results) > 0)
        {
            foreach ($results as $group)
            {
                $root_groups[$name] = $group;
            }
        }
        else
        {
            debug_add("OpenPSA Contacts root group could not be found", MIDCOM_LOG_WARN);

            //Attempt to  auto-initialize the group.
            $_MIDCOM->auth->request_sudo();
            $grp = new midcom_db_group();
            $grp->owner = 0;
            $grp->name = $name;
            $grp->official = midcom::get('i18n')->get_l10n('org.openpsa.contacts')->get($name);
            $ret = $grp->create();
            $_MIDCOM->auth->drop_sudo();
            if (!$ret)
            {
                throw new midcom_error("Could not auto-initialize the module, group creation failed: " . midcom_connection::get_error_string());
            }
            $root_groups[$name] = $grp;
        }

        return $root_groups[$name];
    }

    public function _on_resolve_permalink($topic, $config, $guid)
    {
        try
        {
            $group = new org_openpsa_contacts_group_dba($guid);
            return "group/{$group->guid}/";
        }
        catch (midcom_error $e)
        {
            try
            {
                $person = new org_openpsa_contacts_person_dba($guid);
                return "person/{$person->guid}/";
            }
            catch (midcom_error $e)
            {
                return null;
            }
        }
    }

    /**
     * Support for contacts person merge
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        switch($mode)
        {
            case 'all':
                break;
            case 'future':
                // Contacts does not have future references so we have nothing to transfer...
                return true;
                break;
            default:
                // Mode not implemented
                debug_add("mode {$mode} not implemented", MIDCOM_LOG_ERROR);
                return false;
                break;
        }
        $qb = midcom_db_member::new_query_builder();
        $qb->begin_group('OR');
            // We need the remaining persons memberships later when we compare the two
            $qb->add_constraint('uid', '=', $person1->id);
            $qb->add_constraint('uid', '=', $person2->id);
        $qb->end_group();
        $members = $qb->execute();
        if ($members === false)
        {
            // Some error with QB
            debug_add('QB Error', MIDCOM_LOG_ERROR);
            return false;
        }
        // Transfer memberships
        $membership_map = array();
        foreach ($members as $member)
        {
            if ($member->uid != $person1->id)
            {
                debug_add("Transferred membership #{$member->id} to person #{$person1->id} (from #{$member->uid})");
                $member->uid = $person1->id;
            }
            if (   !isset($membership_map[$member->gid])
                || !is_array($membership_map[$member->gid]))
            {
                $membership_map[$member->gid] = array();
            }
            $membership_map[$member->gid][] = $member;
        }
        unset($members);
        // Merge memberships
        foreach ($membership_map as $members)
        {
            foreach ($members as $member)
            {
                if (count($members) == 1)
                {
                    // We only have one membership in this group, skip rest of the logic
                    if (!$member->update())
                    {
                        // Failure updating member
                        debug_add("Failed to update member #{$member->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                        return false;
                    }
                    continue;
                }

                // TODO: Compare memberships to determine which of them are identical and thus not worth keeping

                if (!$member->update())
                {
                    // Failure updating member
                    debug_add("Failed to update member #{$member->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }

        // Transfer metadata dependencies from classes that we drive
        $classes = array
        (
            'midcom_db_member',
            'org_openpsa_contacts_person_dba',
            'org_openpsa_contacts_group_dba'
        );

        $metadata_fields = array
        (
            'creator' => 'guid',
            'revisor' => 'guid' // Though this will probably get touched on update we need to check it anyways to avoid invalid links
        );

        foreach($classes as $class)
        {
            $ret = org_openpsa_contacts_duplicates_merge::person_metadata_dependencies_helper($class, $person1, $person2, $metadata_fields);
            if (!$ret)
            {
                // Failure updating metadata
                debug_add("Failed to update metadata dependencies in class {$class}, errsrtr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }

        // Copy fields missing from person1 and present in person2 over
        $skip_properties = array
        (
            'id' => true,
            'guid' => true,
        );
        $changed = false;
        foreach($person2 as $property => $value)
        {
            // Copy only simple properties not marked to be skipped missing from person1
            if (   empty($person2->$property)
                || !empty($person1->$property)
                || isset($skip_properties[$property])
                || is_array($value)
                || is_object($value)
                )
            {
                continue;
            }
            $person1->$property = $value;
            $changed = true;
        }
        // Avoid unnecessary updates
        if ($changed)
        {
            if (!$person1->update())
            {
                // Error updating person
                debug_add("Error updating person #{$person1->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }
        // PONDER: sensible way to do the same for parameters ??

        return true;
    }

    private function _get_data_from_url($url)
    {
        $data = array();

        // TODO: Error handling
        $_MIDCOM->load_library('org.openpsa.httplib');
        $client = new org_openpsa_httplib();
        $html = $client->get($url);

        // Check for ICBM coordinate information
        $icbm = org_openpsa_httplib_helpers::get_meta_value($html, 'icbm');
        if ($icbm)
        {
            $data['icbm'] = $icbm;
        }

        // Check for RSS feed
        $rss_url = org_openpsa_httplib_helpers::get_link_values($html, 'alternate');

        if (   $rss_url
            && count($rss_url) > 0)
        {
            $data['rss_url'] = $rss_url[0]['href'];

            // We have a feed URL, but we should check if it is GeoRSS as well
            $_MIDCOM->load_library('net.nemein.rss');

            $rss_content = net_nemein_rss_fetch::raw_fetch($data['rss_url']);

            if (   isset($rss_content->items)
                && count($rss_content->items) > 0)
            {
                if (   array_key_exists('georss', $rss_content->items[0])
                    || array_key_exists('geo', $rss_content->items[0]))
                {
                    // This is a GeoRSS feed
                    $data['georss_url'] = $data['rss_url'];
                }
            }
        }

        if (class_exists('hkit'))
        {
            // We have the Microformats parsing hKit available, see if the page includes a hCard
            $hkit = new hKit();
            $hcards = @$hkit->getByURL('hcard', $url);
            if (   is_array($hcards)
                && count($hcards) > 0)
            {
                // We have found hCard data here
                $data['hcards'] = $hcards;
            }
        }

        return $data;
    }

    /**
     * AT handler for fetching Semantic Web data for person or group
     * @param array $args handler arguments
     * @param object &$handler reference to the cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    function check_url($args, &$handler)
    {
        if (array_key_exists('person', $args))
        {
            // Handling for persons
            try
            {
                $person = new org_openpsa_contacts_person_dba($args['person']);
            }
            catch (midcom_error $e)
            {
                $msg = "Person {$args['person']} not found, error " . $e->getMessage();
                debug_add($msg, MIDCOM_LOG_ERROR);
                $handler->print_error($msg);
                return false;
            }
            if (!$person->homepage)
            {
                $msg = "Person {$person->guid} has no homepage, skipping";
                debug_add($msg, MIDCOM_LOG_ERROR);
                $handler->print_error($msg);
                return false;
            }
            return $this->_check_person_url($person);
        }
        else if (array_key_exists('group', $args))
        {
            // Handling for groups
            try
            {
                $group = new org_openpsa_contacts_group_dba($args['group']);
            }
            catch (midcom_error $e)
            {
                $msg = "Group {$args['group']} not found, error " . $e->getMessage();
                debug_add($msg, MIDCOM_LOG_ERROR);
                $handler->print_error($msg);
                return false;
            }
            if (!$group->homepage)
            {
                $msg = "Group {$group->guid} has no homepage, skipping";
                debug_add($msg, MIDCOM_LOG_ERROR);
                $handler->print_error($msg);
                return false;
            }
            return $this->_check_group_url($group);
        }
        else
        {
            $msg = 'Person or Group GUID not set, aborting';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }
    }

    private function _check_group_url(org_openpsa_contacts_group_dba $group)
    {
        $data = org_openpsa_contacts_interface::_get_data_from_url($group->homepage);

        // Use the data we got
        if (array_key_exists('icbm', $data))
        {
            // We know where the group is located
            $icbm_parts = explode(',', $data['icbm']);
            if (count($icbm_parts) == 2)
            {
                $latitude = (float) $icbm_parts[0];
                $longitude = (float) $icbm_parts[1];
                if (   (   $latitude < 90
                        && $latitude > -90)
                    && (   $longitude < 180
                        && $longitude > -180))
                {
                    $location = new org_routamc_positioning_location_dba();
                    $location->date = time();
                    $location->latitude = $latitude;
                    $location->longitude = $longitude;
                    $location->relation = ORG_ROUTAMC_POSITIONING_RELATION_LOCATED;
                    $location->parent = $group->guid;
                    $location->parentclass = 'org_openpsa_contacts_group_dba';
                    $location->parentcomponent = 'org.openpsa.contacts';
                    $location->create();
                }
                else
                {
                    // This is no earth coordinate, my friend
                }
            }
        }
        // TODO: We can use a lot of other data too
        if (array_key_exists('hcards', $data))
        {
            // Process those hCard values that are interesting for us
            foreach ($data['hcards'] as $hcard)
            {
                $group = $this->_update_from_hcard($group, $hcard);
            }

            $group->update();
        }
        return true;
    }

    private function _check_person_url(org_openpsa_contacts_person_dba $person)
    {
        $data = org_openpsa_contacts_interface::_get_data_from_url($person->homepage);

        // Use the data we got
        if (array_key_exists('georss_url', $data))
        {
            // GeoRSS subscription is a good way to keep track of person's location
            $person->parameter('org.routamc.positioning:georss', 'georss_url', $data['georss_url']);
        }
        else if (array_key_exists('icbm', $data))
        {
            // Instead of using the ICBM position data directly we can subscribe to it so we get modifications too
            $person->parameter('org.routamc.positioning:html', 'icbm_url', $person->homepage);
        }

        if (array_key_exists('rss_url', $data))
        {
            // Instead of using the ICBM position data directly we can subscribe to it so we get modifications too
            $person->parameter('net.nemein.rss', 'url', $data['rss_url']);
        }

        if (array_key_exists('hcards', $data))
        {
            // Process those hCard values that are interesting for us
            foreach ($data['hcards'] as $hcard)
            {
                foreach ($hcard as $key => $val)
                {
                    $person = $this->_update_from_hcard($person, $hcard);
                }
            }

            $person->update();
        }
        return true;
    }

    private function _update_from_hcard($object, $hcard)
    {
        foreach ($hcard as $key => $val)
        {
            switch ($key)
            {
                case 'email':
                    $object->email = $val;
                    break;

                case 'tel':
                    $object->workphone = $val;
                    break;

                case 'note':
                    $object->extra = $val;
                    break;

                case 'photo':
                    // TODO: Importing the photo would be cool
                    break;

                case 'adr':
                    if (array_key_exists('street-address', $val))
                    {
                        $object->street = $val['street-address'];
                    }
                    if (array_key_exists('postal-code', $val))
                    {
                        $object->postcode = $val['postal-code'];
                    }
                    if (array_key_exists('locality', $val))
                    {
                        $object->city = $val['locality'];
                    }
                    if (array_key_exists('country-name', $val))
                    {
                        $object->country = $val['country-name'];
                    }
                    break;
            }
        }
        return $object;
    }
}
?>