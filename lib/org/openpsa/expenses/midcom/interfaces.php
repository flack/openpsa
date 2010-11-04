<?php
/**
 * @package org.openpsa.expenses 
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for org.openpsa.expenses
 * 
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        parent::__construct();
        $this->_component = 'org.openpsa.expenses';

        // Load all mandatory class files of the component here
        $this->_autoload_files = array();
        
        // Load all libraries used by component here
        $this->_autoload_libraries = array();
    }
    
    function _on_initialize()
    {
        return true;
    }

    /**
     * Support for contacts person merge
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        switch($mode)
        {
            case 'all':
                break;
            /* In theory we could have future things (like resource/manager ships), but now we don't support that mode, we just exit */
            case 'future':
                return true;
                break;
            default:
                // Mode not implemented
                debug_add("mode {$mode} not implemented", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
                break;
        }

        // Transfer links from classes we drive

        // ** expense reports **
        $qb_expense = org_openpsa_expenses_expense::new_query_builder();
        $qb_expense->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
        $qb_expense->add_constraint('person', '=', $person2->id);
        $expenses = $qb_expense->execute();
        if ($expenses === false)
        {
            // Some error with QB
            debug_add('QB Error / expenses', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        foreach($expenses as $expense)
        {
            debug_add("Transferred expense #{$expense->id} to person #{$person1->id} (from #{$expense->person})", MIDCOM_LOG_INFO);
            $expense->person = $person1->id;
            if (!$expense->update())
            {
                // Error updating
                debug_add("Failed to update expense #{$expense->id}, errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }


        // Transfer metadata dependencies from classes that we drive
        $classes = array
        (
            'org_openpsa_expenses_expense',
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
                debug_add("Failed to update metadata dependencies in class {$class}, errsrtr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }

        // All done
        return true;
    }

}
?>