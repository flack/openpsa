<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: personlister.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * DM2 Select Type Callback Toolkit: Midgard person Lister
 *
 * The type lists all midgard persons in the system using a hierarchical display.
 *
 * This is mainly intended for displaying / managing the person memberships of
 * Midgard users through the mnrelation type.
 *
 * <b>Configuration options:</b>
 *
 * - <i>string key_field:</i> The field used as key. Valid options are id, guid
 *   midcomid and name. Defaults to guid. 'midcomid' is a person:$identifier combination
 *   suitable for usage in the MidCOM ACL system.
 * - <i>string value_field:</i> The field used as value. Valid is any field available
 *   in the midcom_db_person type, defaults to name.
 *
 * <b>Example usage to manage a user's persons:</b>
 *
 * Use the following DM2 configuration within the person schema:
 *
 * <code>
 * 'persons' => array
 * (
 *     'title'       => 'Persons',
 *     'storage'     => null,
 *     'type'        => 'mnrelation',
 *     'type_config' => Array
 *     (
 *         'mapping_class_name' => 'midcom_db_member',
 *         'master_fieldname' => 'uid',
 *         'member_fieldname' => 'gid',
 *         'master_is_id' => true,
 *         'option_callback' => 'midcom_helper_datamanager2_callback_select_personlister',
 *         'option_callback_args' => Array('key_field' => 'id'),
 *     ),
 *     'widget'      => 'select',
 * ),
 * </code>
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_callback_select_personlister
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
     * The cache of loaded person names, indexed by the selected key field.
     * Note, that this list may be incomplete as long as list_all_done is
     * set.
     *
     * Note, that requested keys which failed to load will also be part of
     * this list but with a null value.
     *
     * @var Array
     * @access private
     */
    var $_loaded_persons = Array();

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
                    debug_add("The value '{$options['key_field']}' is not valid for the option key_field, skipping.",
                        MIDCOM_LOG_INFO);
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
     * Checks if the person key exists. This checks if it is available by looking
     * at the cache. If the key is not present there even after a loading attempt,
     * the key is wrong.
     */
    function key_exists($key)
    {
        if (! array_key_exists($key, $this->_loaded_persons))
        {
            $this->_load_person($key);
        }

        return array_key_exists($key, $this->_loaded_persons);
    }

    /**
     * Returns the name of a loaded person. Since key_exists is always called beforehand,
     * we're sure that it is loaded at this point.
     */
    function get_name_for_key($key)
    {
        return $this->_loaded_persons[$key];
    }

    /**
     * Returns the list of all persons
     */
    function list_all()
    {
        if (! $this->_list_all_done)
        {
            $this->_loaded_persons = Array();

            $qb = midcom_db_person::new_query_builder();
            $qb->add_order('lastname');
            $qb->add_order('firstname');
            $persons = $qb->execute();
            if ($persons)
            {
                foreach ($persons as $person)
                {
                    $key = $this->_get_key($person);
                    $value = $person->{$this->_value_field};
                    $this->_loaded_persons[$key] = $value;
                }
                //asort($this->_loaded_persons, SORT_STRING);
            }
            $this->_list_all_done = true;
        }
        return $this->_loaded_persons;
    }

    /**
     * Returns the key as configured for the given person.
     *
     * @param midcom_db_person $person The person to query.
     * @return mixed the key.
     */
    function _get_key($person)
    {
        if ($this->_key_field == 'midcomid')
        {
            return "person:{$person->guid}";
        }
        else
        {
            return $person->{$this->_key_field};
        }
    }

    /**
     * Loads the person referenced by the key passed. The loaded person is written
     * into the _loaded_persons field. If the list_all_done flag is set, the function
     * will exit silently without doing anything.
     *
     * persons not found will not cause a change in the loaded_persons list.
     *
     * @param mixed $key The key to look up.
     */
    function _load_person($key, $return=false)
    {
        if ($this->_list_all_done)
        {
            return;
        }

        $person = null;
        switch ($this->_key_field)
        {
            case 'id':
            case 'guid':
                $person = new midcom_db_person($key);
                break;

            case 'midcomid':
                $person = new midcom_db_person(substr($key, 6));
                break;

            case 'name':
                $qb = midcom_db_person::new_query_builder();
                $qb->add_order('lastname');
                $qb->add_order('firstname');
                $qb->add_constraint('name', '=', $key);
                $result = $qb->execute();
                if ($result)
                {
                    $person = $result[0];
                }
                break;
        }

        if ($person)
        {
            $key = $this->_get_key($person);
            $value = $person->{$this->_value_field};
            $this->_loaded_persons[$key] = $value;

            if ($return)
            {
                return $person;
            }
        }
    }

    /**
     * Chooser related methods
     */

    function get_key_data($key)
    {
        return $this->_load_person($key, true);
    }

    function run_search($query, &$request)
    {
        $qb = midcom_db_person::new_query_builder();

        $qb->begin_group('OR');
        $qb->add_constraint('firstname', 'LIKE', $query);
        $qb->add_constraint('lastname', 'LIKE', $query);
        $qb->add_constraint('username', 'LIKE', $query);
        $qb->add_constraint('email', 'LIKE', $query);
        $qb->add_constraint('city', 'LIKE', $query);
        $qb->add_constraint('postcode', 'LIKE', $query);
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