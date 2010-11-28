<?php
/**
 * @package org.openpsa.core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class to handle filters for org_openpsa_core
 * filters are applied to currentuser in the parameter-field
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_filter extends midcom_baseclasses_components_purecode
{
    /**
     * contains filters
     *
     * @var Array
     */
    private $_filter = array();

    /**
     * contains possible filter options
     */
    private $_filter_options = array();

    /**
     * constructor - calls the methods _set_filter and _apply_filter for every filter in the given array
     *
     * @param array $filters array including the names of the wanted filters
     * @param mixed $query the querybuilder/collector to filter
     * @param string compare - contains the compare-symbol for the constraint
     * @param array fitler_options - contains options to filter ( needed if the functions don't gather them theirself')
     */
    function __construct($filters, $query, $compare = '=', $filter_options = array())
    {
        $this->_filter_options = $filter_options;
        if (!isset($_POST['unset_filter']))
        {
            foreach ($filters as $filter)
            {
                $this->_set_filter($filter);
                $this->_apply_filter($filter, $query , $compare);
            }
        }
        else
        {
            foreach ($filters as $filter)
            {
                $this->_unset_filter($filter);
            }
        }
    }

    /**
     * Method to set the parameter "org_openpsa_core_filter" of the current-user
     *
     * @param string $filter_name name of the filter which will be saved as parameter "org_openpsa_core_filter""
     *
     */
    private function _set_filter($filter_name)
    {
        $current_user = $_MIDCOM->auth->user->get_storage();
        if (isset($_POST[$filter_name]))
        {
            if (!is_array($_POST[$filter_name]))
            {
                $this->_filter[$filter_name] = array($_POST[$filter_name]);
            }
            else
            {
                $this->_filter[$filter_name] = $_POST[$filter_name];
            }
            $filter_string = implode('|', $this->_filter[$filter_name]);
            if (!$current_user->set_parameter("org_openpsa_core_filter", $filter_name, $filter_string))
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('filter error'), $this->_l10n->get('the handed filter for %s could not be set as parameter'), 'error');
            }
        }
        else if ($filter_string = $current_user->get_parameter("org_openpsa_core_filter", $filter_name))
        {
            $this->_filter[$filter_name] = explode('|', $filter_string);
        }
        else
        {
            //no filter-options
            $this->_filter[$filter_name] = array();
        }
    }

    /**
     * Method to unset the parameter "org_openpsa_core_filter" of the current-user
     *
     * @param string $filter name of the filter to set
     */
    private function _unset_filter($filter)
    {
        $current_user = $_MIDCOM->auth->user->get_storage();
        if (!$current_user->set_parameter("org_openpsa_core_filter", $filter, ""))
        {
            $message_content = sprintf
            (
                $_MIDCOM->i18n->get_string('the handed filter for %s could not be set as parameter', 'org.openpsa.core'),
                $_MIDCOM->i18n->get_string($filter, 'org.openpsa.core')
            );
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('filter error', 'org.openpsa.core'), $message_content, 'error');
        }
    }

    /**
     * Method to edit the given querybuilder or collector
     *
     * @param string $filter_name name of the filter which should be applied
     * @param mixed $query the querybuilder/collector which should the filter be applied to
     */
    private function _apply_filter($filter_name, &$query , $compare_symbol)
    {
        if (array_key_exists($filter_name, $this->_filter))
        {
            $query->begin_group('OR');
            foreach($this->_filter[$filter_name] as $id)
            {
                $query->add_constraint($filter_name, $compare_symbol, (int) $id);
            }
            $query->end_group();
        }
    }

    /**
     * Method which calls the specific list_filter-method, if available, for the parameter $filter
     *
     * @param string $filter_name name of the filter
     */
    function list_filter($filter_name)
    {
        $type_function = 'list_filter_' . $filter_name;
        if (method_exists($this, $type_function))
        {
             return $this->{$type_function}();
        }
        else
        {
            return $this->list_filter_unspecified($filter_name);
        }
    }

    /**
     * Method which creates an array with selectable persons
     * and marks persons who are already filtered as selected
     *
     * @return array $person_array
     */
    function list_filter_person()
    {
        $qb_persons = midcom_db_person::new_query_builder();
        $qb_persons->add_constraint('username', '<>', '');
        $qb_persons->add_constraint('password', '<>', '');

        $person_array = array();

        if (array_key_exists('person', $this->_filter))
        {
            $check_array = array_flip($this->_filter['person']);
        }
        else
        {
            // no persons to filter
            $check_array = array();
        }

        $persons = $qb_persons->execute();
        foreach ($persons as $person)
        {
            $person_array[$person->id]['username'] = "{$person->firstname} {$person->lastname}";
            $person_array[$person->id]['userid'] = $person->id;
            $person_array[$person->id]['selected'] = false;

            if (array_key_exists($person->id, $check_array))
            {
                $person_array[$person->id]['selected'] = true;
            }
        }
        return $person_array;
    }

    /**
     * Method for unspecified filter categories - creates an error message
     *
     * @param string $filter name of the filter
     */
    function list_filter_unspecified($filter)
    {
        if (!empty($this->_filter_options))
        {
            if (array_key_exists($filter, $this->_filter))
            {
                $check_array = array_flip($this->_filter[$filter]);
            }
            else
            {
                // nothing to filter
                $check_array = array();
            }
            //build array with selected & non-selected options
            $return_array = array();
            foreach ($this->_filter_options as $id => $option)
            {
                $return_array[$id]['title'] = $option;
                $return_array[$id]['id'] = $id;
                $return_array[$id]['selected'] = false;
                if (array_key_exists($id, $check_array))
                {
                    $return_array[$id]['selected'] = true;
                }
            }
            return $return_array;
        }
        $message_content = sprintf
        (
            $_MIDCOM->i18n->get_string('no filter available for %s', 'org.openpsa.core'),
            $_MIDCOM->i18n->get_string($filter, 'org.openpsa.core')
        );

        $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('no filter available', 'org.openpsa.core'), $message_content, 'error');
        return false;
    }
}
?>