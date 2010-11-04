<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: grouplister.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * DM2 Select Type Callback Toolkit: Midgard Group Lister
 *
 * The type lists all midgard groups in the system using a hierarchical display.
 *
 * This is mainly intended for displaying / managing the group memberships of
 * Midgard users through the mnrelation type.
 *
 * <b>Configuration options:</b>
 *
 * - <i>string key_field:</i> The field used as key. Valid options are id, guid
 *   midcomid and name. Defaults to guid. 'midcomid' is a group:$identifier combination
 *   suitable for usage in the MidCOM ACL system.
 * - <i>string value_field:</i> The field used as value. Valid is any field available
 *   in the midcom_db_group type, defaults to name.
 *
 * <b>Example usage to manage a user's groups:</b>
 *
 * Use the following DM2 configuration within the person schema:
 *
 * <code>
 * 'groups' => array
 * (
 *     'title'       => 'Gruppen',
 *     'storage'     => null,
 *     'type'        => 'mnrelation',
 *     'type_config' => Array
 *     (
 *         'mapping_class_name' => 'midcom_db_member',
 *         'master_fieldname' => 'uid',
 *         'member_fieldname' => 'gid',
 *         'master_is_id' => true,
 *         'option_callback' => 'midcom_helper_datamanager2_callback_select_grouplister',
 *         'option_callback_args' => Array('key_field' => 'id'),
 *     ),
 *     'widget'      => 'select',
 * ),
 * </code>
 *
 * @todo Child groups are listed in the form "$group, $childgroup". (The traditional
 * indented way isn't used, as this would hamper rendering in view mode; you couldn't
 * see the path there.)
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_callback_select_grouplister
{
    /**
     * The key field used for referencing.
     *
     * @var string
     * @access private
     */
    var $_key_field = 'guid';

    /**
     * The field use for display purposes.
     *
     * @var string
     * @access private
     */
    var $_value_field = 'name';

    /**
     * The cache of loaded group names, indexed by the selected key field.
     * Note, that this list may be incomplete as long as list_all_done is
     * set.
     *
     * Note, that requested keys which failed to load will also be part of
     * this list but with a null value.
     *
     * @var Array
     * @access private
     */
    var $_loaded_groups = Array();

    /**
     * This flag will be set after list_all has been executed to short-cut
     * various lookups where possible.
     *
     * @var boolean
     * @access private
     */
    var $_list_all_done = false;

    /**
     * The default constructor reads in the configuration. See above for allowed
     * options.
     */
    function __construct($options)
    {
        if ($options)
        {
            $this->_process_options($options);
        }

        return true;
    }

    /**
     * Reads and validates the configuration options. Invalid options are logged
     * and ignored.
     */
    function _process_options($options)
    {
        if (array_key_exists('key_field', $options))
        {
            switch ($options['key_field'])
            {
                case 'id':
                case 'guid':
                case 'name':
                case 'midcomid':
                    $this->_key_field = $options['key_field'];
                    break;

                default:
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("The value '{$options['key_field']}' is not valid for the option key_field, skipping.",
                        MIDCOM_LOG_INFO);
                    debug_pop();
                    break;
            }
        }
        if (array_key_exists('value_field', $options))
        {
            $this->_value_field = $options['value_field'];
        }
    }

    /** @ignore */
    function set_type(&$type) {}

    /**
     * Checks if the group key exists. This checks if it is available by looking
     * at the cache. If the key is not present there even after a loading attempt,
     * the key is wrong.
     */
    function key_exists($key)
    {
        if (! array_key_exists($key, $this->_loaded_groups))
        {
            $this->_load_group($key);
        }

        return array_key_exists($key, $this->_loaded_groups);
    }

    /**
     * Returns the name of a loaded group. Since key_exists is always called beforehand,
     * we're sure that it is loaded at this point.
     */
    function get_name_for_key($key)
    {
        return $this->_loaded_groups[$key];
    }

    /**
     * Returns the list of all groups
     */
    function list_all()
    {
        if (! $this->_list_all_done)
        {
            $this->_loaded_groups = Array();

            $qb = midcom_db_group::new_query_builder();
            $groups = $qb->execute();
            if ($groups)
            {
                foreach ($groups as $group)
                {
                    $key = $this->_get_key($group);
                    $value = $group->{$this->_value_field};
                    $this->_loaded_groups[$key] = $value;
                }
                asort($this->_loaded_groups, SORT_STRING);
            }
            $this->_list_all_done = true;
        }
        return $this->_loaded_groups;
    }

    /**
     * Returns the key as configured for the given group.
     *
     * @param midcom_db_group $group The group to query.
     * @return mixed the key.
     */
    function _get_key($group)
    {
        if ($this->_key_field == 'midcomid')
        {
            return "group:{$group->guid}";
        }
        else
        {
            return $group->{$this->_key_field};
        }
    }

    /**
     * Loads the group referenced by the key passed. The loaded group is written
     * into the _loaded_groups field. If the list_all_done flag is set, the function
     * will exit silently without doing anything.
     *
     * Groups not found will not cause a change in the loaded_groups list.
     *
     * @param mixed $key The key to look up.
     */
    function _load_group($key, $return=false)
    {
        if ($this->_list_all_done)
        {
            return;
        }

        $group = null;
        switch ($this->_key_field)
        {
            case 'id':
                try
                {
                    $group = new midcom_db_group((int) $key);
                }
                catch (Exception $e)
                {
                    return;
                }
                break;
            case 'guid':
                try
                {
                    $group = new midcom_db_group($key);
                }
                catch (Exception $e)
                {
                    return;
                }
                break;
            case 'midcomid':
                $group = new midcom_db_group(substr($key, 6));
                break;
            case 'name':
                $qb = midcom_db_group::new_query_builder();
                $qb->add_constraint('name', '=', $key);
                $result = $qb->execute();
                if ($result)
                {
                    $group = $result[0];
                }
                else
                {
                    return;
                }
                break;
        }

        if (   $group
            && $group->guid)
        {
            $key = $this->_get_key($group);
            $value = $group->{$this->_value_field};
            $this->_loaded_groups[$key] = $value;
            if ($return)
            {
                return $group;
            }
        }
    }

    /**
     * Chooser related methods
     */
    function get_key_data($key)
    {
        return $this->_load_group($key, true);
    }

    function run_search($query, &$request)
    {
        $qb = midcom_db_group::new_query_builder();

        $qb->begin_group('OR');
        $qb->add_constraint('name', 'LIKE', $query);
        $qb->add_constraint('official', 'LIKE', $query);
        $qb->end_group();

        $results = $qb->execute();

        if (count($results) <= 0)
        {
            return false;
        }

        return $results;
    }

}

?>