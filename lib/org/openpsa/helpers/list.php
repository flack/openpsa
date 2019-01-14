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
    private static $_seen = [];

    /**
     * Function for listing groups task/salesproject contacts are members of
     *
     * @param midcom_core_dbaobject $task The task/salesproject we're working with
     * @param string $mode By which property should groups be listed
     * @param array $contacts Default contacts for nonpersistent objects
     */
    public static function task_groups(midcom_core_dbaobject $task, $mode = 'id', array $contacts = [])
    {
        $ret = [0 => ''];
        self::$_seen = [];

        if (!in_array($mode, ['id', 'guid'])) {
            debug_add('Mode ' . $mode . ' not supported', MIDCOM_LOG_ERROR);
            return $ret;
        }

        //Make sure the currently selected customer (if any) is listed
        if ($task->customer > 0) {
            //Make sure we can read the current customer for the name
            midcom::get()->auth->request_sudo('org.openpsa.helpers');
            self::task_groups_put($ret, $mode, $task->customer);
            midcom::get()->auth->drop_sudo();
        }
        if (empty($contacts)) {
            $task->get_members();
            $contacts = $task->contacts;

            if (empty($contacts)) {
                return $ret;
            }
        }

        $mc = midcom_db_member::new_collector();
        $mc->add_constraint('uid', 'IN', array_keys($contacts));
        /* Skip magic groups */
        $mc->add_constraint('gid.name', 'NOT LIKE', '\_\_%');
        $memberships = $mc->get_values('gid');

        foreach ($memberships as $gid) {
            self::task_groups_put($ret, $mode, $gid);
        }

        reset($ret);
        asort($ret);
        return $ret;
    }

    private static function task_groups_put(array &$ret, $mode, $company_id)
    {
        if (empty(self::$_seen[$company_id])) {
            try {
                $company = new org_openpsa_contacts_group_dba($company_id);
            } catch (midcom_error $e) {
                return;
            }
            self::$_seen[$company->id] = true;

            $ret[$company->$mode] = $company->get_label();
        }
    }

    /**
     * List tasks user can see
     */
    public static function projects()
    {
        //Only query once per request
        static $cache = null;
        if (is_null($cache)) {
            $cache = [
                'all' => midcom::get()->i18n->get_string('all', 'midcom')
            ];

            $qb = org_openpsa_projects_project::new_query_builder();
            $qb->add_order('title');

            foreach ($qb->execute() as $task) {
                $cache[$task->guid] = $task->title;
            }
        }
        return $cache;
    }

    /**
     * List virtual groups of user
     */
    public static function workgroups($add_me = 'last', $show_members = false)
    {
        if (!midcom::get()->auth->user) {
            return [];
        }
        static $cache = [];
        // List user's ACL groups for usage in DM arrays
        $array_name = $add_me . '_' . $show_members;
        if (!array_key_exists($array_name, $cache)) {
            $cache[$array_name] = [];
            if ($add_me == 'first') {
                $cache[$array_name][midcom::get()->auth->user->id] = midcom::get()->i18n->get_string('me', 'org.openpsa.contacts');
            }

            $users_groups = midcom::get()->auth->user->list_memberships();
            foreach ($users_groups as $key => $vgroup) {
                if (is_object($vgroup)) {
                    $cache[$array_name][$key] = $vgroup->name;
                    if ($show_members) {
                        $vgroup_members = $vgroup->list_members();
                        foreach ($vgroup_members as $key2 => $person) {
                            $cache[$array_name][$key2] = '&nbsp;&nbsp;&nbsp;' . $person->name;
                        }
                    }
                } else {
                    $cache[$array_name][$key] = $vgroup;
                }
            }

            asort($cache[$array_name]);

            if ($add_me == 'last') {
                $cache[$array_name][midcom::get()->auth->user->id] = midcom::get()->i18n->get_string('me', 'org.openpsa.contacts');
            }
        }
        return $cache[$array_name];
    }
}
