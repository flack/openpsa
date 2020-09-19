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
     * List tasks user can see
     */
    public static function projects() : array
    {
        //Only query once per request
        static $cache = null;
        if ($cache === null) {
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
    public static function workgroups(string $add_me = 'last', bool $show_members = false) : array
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
            foreach ($users_groups as $key => $group) {
                $cache[$array_name][$key] = $group->name;
                if ($show_members) {
                    foreach ($group->list_members() as $key2 => $person) {
                        $cache[$array_name][$key2] = '&nbsp;&nbsp;&nbsp;' . $person->name;
                    }
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
