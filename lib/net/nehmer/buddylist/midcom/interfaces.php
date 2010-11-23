<?php

/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 26158 2010-05-22 18:05:34Z flack $
 * @version $Id: interfaces.php 26158 2010-05-22 18:05:34Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Marketplace component.
 *
 * This is a component geared for communities offering a marketplace service. It allows
 * categorization.
 *
 * The component is built to work on the data delivered by the account component, as their
 * values are used as a basis for the new market entry system.
 *
 * The topics serve only as containers for hooking up the component, its actual data is stored
 * in its own table, for more flexible management / querying. If you need to have different
 * market places on the same site(group), you need to distinguish them by different category
 * configurations.
 *
 * @package net.nehmer.buddylist
 */
class net_nehmer_buddylist_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        define ('NET_NEHMER_BUDDYLIST_LEAFID_PENDING', 1);

        $this->_component = 'net.nehmer.buddylist';
    }

    /**
     * The delete handler will drop all entries associated with any person record that has been
     * deleted. We don't need to check for watched classes at this time, since we have no other
     * watches defined.
     */
    function _on_watched_dba_delete($object)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->begin_group('OR');
        $qb->add_constraint('account', '=', $object->guid);
        $qb->add_constraint('buddy', '=', $object->guid);
        $qb->end_group();
        $result = $qb->execute();
        if ($result)
        {
            foreach ($result as $entry)
            {
                debug_add("Deleting Buddylist Entry entry {$entry->guid} ID {$entry->id}.");
                $entry->delete();
            }
        }
        debug_pop();
    }

    /**
     * Checks the index documents' permission using the unindexed _anonymous_read field
     * associated with the document.
     */
    function _on_check_document_permissions (&$document, $config, $topic)
    {
        return ($_MIDCOM->auth->user !== null);
    }

    /**
     * Resolves entry guids into view URLs.
     */
    function _on_resolve_permalink($topic, $config, $guid)
    {
        if ($config->get('index_to'))
        {
            // We only resolve permalinks on indexed topics, as non-indexed are normally
            // "second-level views" of other topics.
            return null;
        }
        $object = $_MIDCOM->dbfactory->get_object_by_guid($guid);
        if (is_a($object, 'net_nehmer_buddylist_entry'))
        {
            return "entry/view/{$object->guid}.html";
        }
        return null;
    }

    /**
     * Support for merging persons via contacts, merges buddylist entries
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        switch($mode)
        {
            case 'all':
                break;
            case 'future':
                // Buddylist does not have future references so we have nothing to transfer...
                return true;
                break;
            default:
                // Mode not implemented
                debug_add("mode {$mode} not implemented", MIDCOM_LOG_ERROR);
                return false;
                break;
        }
        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->begin_group('OR');
            // We need the remaining persons buddies later when we compare the two
            $qb->add_constraint('buddy', '=', $person1->guid);
            $qb->add_constraint('account', '=', $person1->guid);
            $qb->add_constraint('buddy', '=', $person2->guid);
            $qb->add_constraint('account', '=', $person2->guid);
        $qb->end_group();
        $buddies = $qb->execute();

        if ($buddies === false)
        {
            // Some error with QB
            return false;
        }
        // Transfer buddies
        foreach ($buddies as $buddy)
        {
            $changed = false;
            if ($buddy->account == $person2->guid)
            {
                debug_add("Transferred buddy->account #{$buddy->id} to person #{$person->id} (from {$buddy->account})");
                $buddy->account = $person1->guid;
                $changed = true;
            }
            if ($buddy->buddy == $person2->guid)
            {
                debug_add("Transferred buddy->buddy #{$buddy->id} to person #{$person->id} (from {$buddy->buddy})");
                $buddy->buddy = $person1->guid;
                $changed = true;
            }
            if ($changed)
            {
                if (!$buddy->update())
                {
                    // Failure updating
                    return false;
                }
            }
        }

        // TODO: Check for duplicates and remove those (also from the buddies array...)

        // TODO: check version and transfer 1.8 metadata where needed

        // All done
        return true;
    }
}
?>