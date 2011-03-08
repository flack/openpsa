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
        $seen = array();

        if (!$_MIDCOM->componentloader->load_graceful('org.openpsa.contacts'))
        {
            //PONDER: Maybe we should raise a fatal error ??
            return $ret;
        }

        //Make sure the currently selected customer (if any) is listed
        if (   $task->customer
            && !isset($ret[$task->customer]))
        {
            //Make sure we can read the current customer for the name
            $_MIDCOM->auth->request_sudo();
            $company = new org_openpsa_contacts_group_dba($task->customer);
            $_MIDCOM->auth->drop_sudo();
            $seen[$company->id] = true;
            self::task_groups_put($ret, $mode, $company);
        }
        $task->get_members();
        if (   !is_array($task->contacts)
            || count($task->contacts) == 0)
        {
            return $ret;
        }

        $mc = midcom_db_member::new_collector('metadata.deleted', false);
        $mc->add_value_property('gid');
        $mc->add_constraint('uid', 'IN', array_keys($task->contacts));
        $mc->execute();

        $memberships = @$mc->list_keys();
        if (   !is_array($memberships)
            || count($memberships) == 0)
        {
            return $ret;
        }

        reset ($memberships);
        foreach ($memberships as $guid => $empty)
        {
            $gid = $mc->get_subkey($guid, 'gid');
            if (isset($seen[$gid])
                && $seen[$gid] == true)
            {
                continue;
            }
            try
            {
                $company = new org_openpsa_contacts_group_dba($gid);
                /* Skip magic groups */
                if (preg_match('/^__/', $company->name))
                {
                    continue;
                }
            }
            catch (midcom_error $e)
            {
                continue;
            }
            $seen[$company->id] = true;
            self::task_groups_put($ret, $mode, $company);
        }
        reset($ret);
        asort($ret);
        return $ret;
    }

    static function task_groups_put(&$ret, &$mode, &$company)
    {
        if ($company->official)
        {
            $name = $company->official;
        }
        else if (   !$company->official
                && $company->name)
        {
            $name = $company->name;
        }
        else
        {
            $name = "#{$company->id}";
        }
        switch ($mode)
        {
            case 'id':
                $ret[$company->id] = $name;
                break;
            case 'guid':
                $ret[$company->guid] = $name;
                break;
            default:
                //Mode not supported
                return;
                break;
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

            // Workgroup filtering
            if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
            {
                $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
            }

            $qb->add_order('title');

            //Execute
            $ret = $qb->execute();
            if (   is_array($ret)
                && count($ret) > 0)
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
            if ($_MIDCOM->auth->user)
            {
                if ($add_me == 'first')
                {
                    //TODO: Localization
                    $GLOBALS[$array_name][$_MIDCOM->auth->user->id] = 'me';
                }

                $users_groups = $_MIDCOM->auth->user->list_memberships();
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
                        && is_object($vgroup)
                        )
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
                    $GLOBALS[$array_name][$_MIDCOM->auth->user->id] = 'me';
                }
            }
        }
        return $GLOBALS[$array_name];
    }

    /**
     * Helper function for listing virtual groups of user
     *
     * @return Array List of persons appropriate for the current selection
     * @todo This doesn't seem to be used anywhere
     */
    static function resources()
    {
        // List members of selected ACL group for usage in DM arrays
        if (!array_key_exists('org_openpsa_helpers_resources', $GLOBALS))
        {
            $GLOBALS['org_openpsa_helpers_resources'] = array();
            //Safety
            if (!isset($GLOBALS['org_openpsa_core_workgroup_filter']))
            {
                $GLOBALS['org_openpsa_core_workgroup_filter'] = 'all';
            }

            if (   $GLOBALS['org_openpsa_core_workgroup_filter'] == 'all'
                && $_MIDCOM->auth->user)
            {
                // Populate only the user himself to the list
                $user = $_MIDCOM->auth->user->get_storage();
                $GLOBALS['org_openpsa_helpers_resources'][$user->id] = true;
            }
            else
            {
                $group = & $_MIDCOM->auth->get_group($GLOBALS['org_openpsa_core_workgroup_filter']);
                if ($group)
                {
                    $members = $group->list_members();
                    foreach ($members as $person)
                    {
                        $member = $person->get_storage();
                        $GLOBALS['org_openpsa_helpers_resources'][$member->id] = true;
                    }
                }
            }
        }
        return $GLOBALS['org_openpsa_helpers_resources'];
    }
}
?>