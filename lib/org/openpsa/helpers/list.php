<?php
/**
 * Collection of list functions for OpenPSA
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_list
{
    private static $_seen = array();

    /**
     * Function for listing groups tasks contacts are members of
     *
     * @param org_openpsa_projects_task_dba &$task The task we're working with
     * @param string $mode By which property should groups be listed
     */
    static function task_groups(&$task, $mode = 'id')
    {
        //TODO: Localize something for the empty choice ?
        $ret = array(0 => '');
        self::$_seen = array();

        //Make sure the currently selected customer (if any) is listed
        if ($task->customer > 0)
        {
            //Make sure we can read the current customer for the name
            midcom::get('auth')->request_sudo();
            self::task_groups_put($ret, $mode, $task->customer);
            midcom::get('auth')->drop_sudo();
        }
        $task->get_members();

        if (   !is_array($task->contacts)
            || count($task->contacts) == 0)
        {
            return $ret;
        }

        $mc = midcom_db_member::new_collector('metadata.deleted', false);
        $mc->add_constraint('uid', 'IN', array_keys($task->contacts));
        /* Skip magic groups */
        $mc->add_constraint('gid.name', 'NOT LIKE', '\_\_%');
        $memberships = $mc->get_values('gid');

        if (empty($memberships))
        {
            return $ret;
        }

        foreach ($memberships as $gid)
        {
            self::task_groups_put($ret, $mode, $gid);
        }
        reset($ret);
        asort($ret);
        return $ret;
    }

    static function task_groups_put(&$ret, $mode, $company_id)
    {
        if (!empty(self::$_seen[$company_id]))
        {
            return;
        }
        try
        {
            $company = new org_openpsa_contacts_group_dba($company_id);
        }
        catch (midcom_error $e)
        {
            return;
        }
        self::$_seen[$company->id] = true;

        switch ($mode)
        {
            case 'id':
                $ret[$company->id] = $company->official;
                break;
            case 'guid':
                $ret[$company->guid] = $company->official;
                break;
            default:
                debug_add('Mode ' . $mode . ' not supported', MIDCOM_LOG_ERROR);
                return;
        }
    }

    /**
     * Helper function for listing tasks user can see
     */
    static function projects($add_all = false)
    {
        //Only query once per request
        static $cache = null;
        if (is_null($cache))
        {
            $cache = array();
            if ($add_all)
            {
                //TODO: Localization
                $cache['all'] = 'all';
            }

            $qb = org_openpsa_projects_project::new_query_builder();
            $qb->add_order('title');
            $ret = $qb->execute();

            if (count($ret) > 0)
            {
                foreach ($ret as $task)
                {
                    $cache[$task->guid] = $task->title;
                }
            }
        }
        return $cache;
    }

    /**
     * Helper function for listing virtual groups of user
     */
    static function workgroups($add_me = 'last', $show_members = false)
    {
        // List user's ACL groups for usage in DM arrays
        $array_name = 'org_openpsa_helpers_workgroups_cache_' . $add_me . '_' . $show_members;
        if (!array_key_exists($array_name, $GLOBALS))
        {
            $GLOBALS[$array_name] = array();
            if (midcom::get('auth')->user)
            {
                if ($add_me == 'first')
                {
                    //TODO: Localization
                    $GLOBALS[$array_name][midcom::get('auth')->user->id] = 'me';
                }

                $users_groups = midcom::get('auth')->user->list_memberships();
                foreach ($users_groups as $key => $vgroup)
                {
                    if (is_object($vgroup))
                    {
                        $label = $vgroup->name;
                    }
                    else
                    {
                        $label = $vgroup;
                    }

                    $GLOBALS[$array_name][$key] = $label;

                    //TODO: get the vgroup object based on the key or something, this check fails always.
                    if (   $show_members
                        && is_object($vgroup))
                    {
                        $vgroup_members = $vgroup->list_members();
                        foreach ($vgroup_members as $key2 => $person)
                        {
                            $GLOBALS[$array_name][$key2] = '&nbsp;&nbsp;&nbsp;' . $person->name;
                        }
                    }
                }

                asort($GLOBALS[$array_name]);

                if ($add_me == 'last')
                {
                    //TODO: Localization
                    $GLOBALS[$array_name][midcom::get('auth')->user->id] = 'me';
                }
            }
        }
        return $GLOBALS[$array_name];
    }
}
?>