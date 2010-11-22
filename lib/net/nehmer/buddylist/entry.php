<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: entry.php 24773 2010-01-18 08:15:45Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Marketplace entry class
 *
 * Entries have no uplink at this time.
 *
 * @package net.nehmer.buddylist
 */
class net_nehmer_buddylist_entry extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'net_nehmer_buddylist_entry_db';
    
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }
        
    function get_parent_guid_uncached()
    {
        if ($this->account)
        {
            $parent = new midcom_db_person($this->account);
            return $parent->guid;
        }
        return null;
    }
    
    /**
     * Human-readable label for cases like Asgard navigation
     */
    function get_label()
    {
        if ($this->buddy)
        {
            $buddy = new midcom_db_person($this->buddy);
            return sprintf($_MIDCOM->i18n->get_string('buddy %s', 'net.nehmer.buddylist'), $buddy->name);
        }
        return "buddy #{$this->id}";
    }

    /**
     * Internal helper, prepares the QB instance required for the unapproved
     * retrieval functions.
     *
     * @param midcom_core_user $user The user of whom the pending approvals should be listed.
     * @access protected
     */
    function _get_unapproved_qb($user)
    {
        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->add_constraint('buddy', '=', $user->guid);
        $qb->add_constraint('isapproved', '=', false);
        $qb->add_constraint('blacklisted', '=', false);
        return $qb;
    }

    /**
     * Internal helper, prepares the QB instance required for the buddy
     * retrieval functions.
     *
     * @param midcom_core_user $user The user of whom the buddies should be listed.
     * @access protected
     */
    function _get_buddy_qb($user)
    {
        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->add_constraint('account', '=', $user->guid);
        $qb->add_constraint('isapproved', '=', true);
        $qb->add_constraint('blacklisted', '=', false);
        return $qb;
    }

    /**
     * Lists all entries belonging to a given user.
     *
     * Due to the buddylists' restrictive permission set, this will usually only succeed
     * for the current user unless we have administrator privileges.
     *
     * Unapproved entries are filtered from the resultset.
     *
     * This function must be called statically.
     *
     * @param midcom_core_user $user The user of whom the entries should be listed,
     *    this defaults to the currently active user.
     * @return Array A list of username => midcom_core_user pairs.
     * @static
     */
    function list_buddies($user = null)
    {
        if ($user === null)
        {
            $_MIDCOM->auth->require_valid_user();
            $user =& $_MIDCOM->auth->user;
        }
        
        $qb = net_nehmer_buddylist_entry::_get_buddy_qb($user);
        $buddies = $qb->execute();

        if (! $buddies)
        {
            // Error (this will be logged) or empty list (this won't). Both return an empty list.
            if ($buddies === false)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Failed to query the buddy list. Last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
                debug_pop();
            }
            return Array();
        }

        $result = Array();

        foreach ($buddies as $buddy)
        {
            $user =& $buddy->get_buddy_user();
            $result[$user->username] =& $user;
        }

        ksort($result, SORT_STRING);
        return $result;
    }

    /**
     * Lists all entries pending approval by a given user.
     *
     * Due to the buddylists' restrictive permission set, this will usually only succeed
     * for the current user unless we have administrator privileges.
     *
     * The "buddy" accounts too have owner privileges for these records, as this privilege
     * is set during creation. This allows the user to update or delete the record while
     * deciding upon approval.
     *
     * This function must be called statically.
     *
     * @param midcom_core_user $user The user of whom the pending approvals should be listed,
     *    this defaults to the currently active user.
     * @return Array A QB resultset or false on failure.
     * @static
     */
    function list_unapproved($user = null)
    {
        if ($user === null)
        {
            $_MIDCOM->auth->require_valid_user();
            $user =& $_MIDCOM->auth->user;
        }

        $qb = net_nehmer_buddylist_entry::_get_unapproved_qb($user);
        return $qb->execute();
    }

    /**
     * Gets the count of pending buddylist requests. This call will automatically work for
     * the current user only. This call works unchecked, but this should not make a difference
     * unless the DB is inconsistent.
     *
     * This function must be called statically and requires an authenticated user.
     *
     * @return int The pending approval count.
     * @static
     */
    function get_unapproved_count()
    {
        $_MIDCOM->auth->require_valid_user();
        $user =& $_MIDCOM->auth->user;

        $qb = net_nehmer_buddylist_entry::_get_unapproved_qb($user);
        return $qb->count_unchecked();
    }

    /**
     * Returns the count of online buddies. This is built on get_online_buddies,
     * to rely on its caching. See there for details.
     *
     * @return int Online buddy count.
     */
    function get_online_buddy_count()
    {
        return count(net_nehmer_buddylist_entry::list_online_buddies());
    }

    /**
     * Lists all buddies of the current user that are currently online.
     *
     * This function must be called statically and requires a valid user.
     *
     * Be aware that the result of this call is cached within a single request
     * using a static variable to avoid doing the expensive online check more then
     * once.
     *
     * The list is sorted by username.
     *
     * @return Array A list of midcom_core_user instances indexed by their current
     *     user name (use the GUID for all referrals!).
     * @static
     */
    function list_online_buddies()
    {
        static $cache = Array();

        $_MIDCOM->auth->require_valid_user();
        $user =& $_MIDCOM->auth->user;

        if (array_key_exists($user->guid, $cache))
        {
            return $cache[$user->guid];
        }
        else
        {
            $qb = net_nehmer_buddylist_entry::_get_buddy_qb($user);
            $buddies = $qb->execute();

            $result = Array();

            foreach ($buddies as $buddy)
            {
                $buddy_user =& $buddy->get_buddy_user();
                if ($buddy_user->is_online() == 'online')
                {
                    $result[$buddy_user->username] =& $buddy_user;
                }
            }

            ksort($result, SORT_STRING);

            $cache[$user->guid] = $result;
            return $result;
        }
    }

    /**
     * Creation handler, grants owner permissions to the buddy user for this
     * buddy object, so that he can later approve / reject the request. For
     * safety reasons, the owner privilege towards the account user is also
     * created, so that there is no discrepancy later in case administrators
     * create the object.
     */
    function _on_created()
    {
        $this->set_privilege('midgard:owner', "user:{$this->buddy}");
        $this->set_privilege('midgard:owner', "user:{$this->account}");
    }

    /**
     * The pre-creation hook sets the added field to the current timestamp if and only if
     * it is unset.
     */
    function _on_creating()
    {
        $this->_activitystream_verb = 'http://activitystrea.ms/schema/1.0/make-friend';
        if (! $this->added)
        {
            $this->added = time();
        }
        return true;
    }

    /**
     * Approves the current record. This will flag the record as approved and will then
     * drop the owner privileges of the current user. If the user does not have owner
     * privileges for the record, an access denied error is triggered.
     *
     * The user will also need privilege update privilieges on the person referenced by the buddy
     * GUID, which we are usually ourself.
     *
     * Upon approval, the flag is set accordingly, and the ownership privileges are not
     * dropped to allow later modification of the buddy list state (not yet implmented).
     * Then, the isonline privilege is granted for the respective user so that the online
     * state may be seen.
     */
    function approve()
    {
        // Load person and check privileges
        $buddy_user =& $this->get_buddy_user();
        $buddy = $buddy_user->get_storage();
        if (! $buddy)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Could not load the person {$this->buddy} to adjust the privileges accordingly.");
            // This will exit.
        }

        $_MIDCOM->auth->require_do('midgard:owner', $this);
        $_MIDCOM->auth->require_do('midgard:update', $buddy);
        $_MIDCOM->auth->require_do('midgard:privileges', $buddy);
        
        // Invalidate cache for both users
        $_MIDCOM->cache->invalidate($buddy_user->guid);
        $_MIDCOM->cache->invalidate($buddy->guid);

        $this->isapproved = true;
        $this->update();
        $buddy->set_privilege('midcom:isonline', "user:{$this->account}");
    }

    /**
     * Rejects approval of the current record. This will drop the record. If the user does not
     * have owner privileges for the record, an access denied error is triggered.
     *
     * The user will also need privilege update privilieges on the person referenced by the buddy
     * GUID, which we are usually ourself.
     */
    function reject()
    {
        // Check privileges
        $_MIDCOM->auth->require_do('midgard:owner', $this);
        $this->delete();
    }

    /**
     * When a buddy is deleted, we use sudo to drop the isonline privilege from the buddy
     * again (of course if and only if this record is approved).
     */
    function _on_deleted()
    {
        if ($this->isapproved)
        {
            if (! $_MIDCOM->auth->request_sudo('net.nehmer.buddylist'))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to optain sudo privileges for the buddylist_entry on_deleted handler');
                // This will exit.
            }

            // Get buddy person
            $buddy = new midcom_db_person($this->buddy);
            $buddy->unset_privilege('midcom:isonline', "user:{$this->account}");

            $_MIDCOM->auth->drop_sudo();
        }
    }

    /**
     * Hide entries for not authenticated users, allow creation for all authenticated
     * users.
     */
    function get_class_magic_default_privileges()
    {
        return Array
        (
            'EVERYONE' => Array('midgard:read' => MIDCOM_PRIVILEGE_DENY),
            'ANONYMOUS' => Array(),
            'USERS' => Array
            (
                'midgard:create' => MIDCOM_PRIVILEGE_ALLOW,
                'midgard:read' => MIDCOM_PRIVILEGE_ALLOW
            )
        );
    }

    /**
     * Returns the user object associated with the account field.
     *
     * @return midcom_core_user User account (beware of the reference).
     */
    function & get_account_user()
    {
        return $_MIDCOM->auth->get_user($this->account);
    }

    /**
     * Returns the user object associated with the buddy field.
     *
     * @return midcom_core_user User account (beware of the reference).
     */
    function & get_buddy_user()
    {
        return $_MIDCOM->auth->get_user($this->buddy);
    }

    /**
     * This call checks whether the given user is already on our buddy list. This is used
     * in the pending listing to hide the approve & add button appropriately. Note that
     * even unapproved requests count here.
     *
     * @param midcom_core_user $user The user to check for.
     * @return boolean True, if the user already is on your buddy list.
     */
    function is_on_buddy_list(&$buddy, $user = null)
    {
        if ($user === null)
        {
            $_MIDCOM->auth->require_valid_user();
            $user =& $_MIDCOM->auth->user;
        }

        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->add_constraint('account', '=', $user->guid);
        $qb->add_constraint('buddy', '=', $buddy->guid);
        if ($qb->count_unchecked())
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

?>