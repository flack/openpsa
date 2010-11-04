<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Resolves smart-campaign rules array to one or more QB instances
 * with correct constraints, and merges the results.
 *
 * Rules array structure:
 * <code>
 * array(
 *    'type' => 'AND',
 *    'classes' => array
 *    (
 *        array
 *        (
 *            'type' => 'OR',
 *            'class' => 'org_openpsa_contacts_person_dba',
 *            'rules' => array
 *            (
 *                array
 *                (
 *                    'property' => 'email',
 *                    'match' => 'LIKE',
 *                    'value' => '%@%'
 *                ),
 *                array
 *                (
 *                    'property' => 'handphone',
 *                    'match' => '<>',
 *                    'value' => ''
 *                ),
 *            ),
 *        ),
 *        array
 *        (
 *            'type' => 'AND',
 *            'class' => 'midgard_parameter',
 *            'rules' => array
 *            (
 *                array
 *                (
 *                    'property' => 'domain',
 *                    'match' => '=',
 *                    'value' => 'openpsa_test'
 *                ),
 *                array
 *                (
 *                    'property' => 'name',
 *                    'match' => '=',
 *                    'value' => 'param_match'
 *                ),
 *                array
 *                (
 *                    'property' => 'value',
 *                    'match' => '=',
 *                    'value' => 'bar'
 *                ),
 *            ),
 *        ),
 *    ),
 * ),
 * </code>
 *
 * NOTE: subgroups are processed before rules, subgroups must match class of parent group
 * until midgard core has the new infinite JOINs system. The root level group array is
 * named 'classes' because there should never be be two groups on this level using the same class
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_ruleresolver
{
    var $_qbs = array(); //QB instances used by class
    var $_results =  array(); //Resultsets from said QBs
    var $_rules = null; //Copy of rules as received
    var $_seek =  array(); //index for quickly finding out which persons are found via which classes
    var $_result_mc = null; // Contact-qb containing results


    function __construct($rules = false)
    {
        // Make sure all supported classes are loaded
        $_MIDCOM->componentloader->load_graceful('org.maemo.devcodes');

        // if querybuilder is used response-time will increase -> set_key_property hast to be removed
        //$this->_result_mc = org_openpsa_contacts_person_dba::new_query_builder();
        $this->_result_mc = org_openpsa_contacts_person_dba::new_collector('sitegroup' , $_MIDGARD['sitegroup']);
        if ($rules)
        {
            return $this->resolve($rules);
        }
    }

    /**
     * Recurses trough the rules array and creates QB instances & constraints as needed
     * @param array $rules rules array
     * @return boolean indicating success/failure
     */
    function resolve($rules)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $this->_rules = $rules;
        if (!is_array($rules))
        {
            debug_add('rules is not an array', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if (!array_key_exists('classes', $rules))
        {
            debug_add('rules[classes] is not defined', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if (!is_array($rules['classes']))
        {
            debug_add('rules[classes] is not an array', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        //start with first group
        $this->_result_mc->begin_group(strtoupper($rules['groups']));
        reset ($rules['classes']);
        //iterate over groups
        foreach ($rules['classes'] as $group)
        {
            $this->_resolve_rule_group($group);
        }
        $this->_result_mc->end_group();

        debug_pop();
        return true;
    }

    /**
     * Executes the QBs instanced via resolve, merges results and returns
     * single array of persons (or false in case of failure)
     */
    function execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!is_array($this->_rules))
        {
            debug_add('this->_rules is not an array', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $this->_result_mc->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
        $this->_result_mc->set_key_property('guid');
        $this->_result_mc->add_value_property('lastname');
        $this->_result_mc->add_value_property('firstname');
        $this->_result_mc->add_value_property('email');
        //$this->_result_mc->add_value_property('guid');
        $this->_result_mc->add_value_property('id');
        $this->_result_mc->add_order('lastname', 'ASC');
        $this->_result_mc->execute();
        $results = $this->_result_mc->list_keys();
        $ret = array();
        //foreach($results as $person)
        foreach($results as $key => $value)
        {
            $ret[$this->_result_mc->get_subkey($key , 'id')] = array(
                'lastname' => $this->_result_mc->get_subkey($key , 'lastname'),
                'firstname' => $this->_result_mc->get_subkey($key , 'firstname'),
                'email' => $this->_result_mc->get_subkey($key , 'email'),
                'guid' => $key,
                );
            //$ret[$person->guid] = $person;
        }

        debug_pop();
        return $ret;
    }
    /**
     * Resolves the rules in a single rule group
     * @param array $group single group from rules array
     * @param string $match_class wanted class in group
     * @return boolean indicating success/failure
     */
    function _resolve_rule_group($group, $match_class = false)
    {

        debug_push_class(__CLASS__, __FUNCTION__);
        if (!is_array($group))
        {
            debug_add('group is not an array', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if (   $match_class
            && (
                $group['class'] != $match_class
                )
            )
        {
            debug_add("{$group['class']} != {$match_class}, unmatched classes where match required", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        //check if rule is a group
        if (array_key_exists('groups', $group))
        {
            debug_add("calling qb->begin_group(strtoupper({$group['groups']}))");
            $this->_result_mc->begin_group(strtoupper($group['groups']));
            foreach ($group['classes'] as $subgroup)
            {
                $this->_resolve_rule_group($subgroup);
            }
            $this->_result_mc->end_group();
            debug_add("calling qb->end_group(strtoupper({$group['groups']}))");
        }
        else
        {
            if (!array_key_exists('rules', $group))
            {
                debug_add('group[rules] is not defined', MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            if (!is_array($group['rules']))
            {
                debug_add('group[rules] is not an array', MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            $this->add_rules($group['rules'] , $group['class']);
        }

        debug_pop();
        return true;
    }

    /** Iterates over passed rules for given class and calls functions
     * to add rules to the querybuilder/collector
     * @param array $rules array containing rules
     * @param string $class containing name of class for the rules
     */
    function add_rules($rules , $class)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        debug_add("try to build rules for class: {$class}");

        //special case parameters - uses 3 rules standard
        if($class == 'midgard_parameter')
        {
            $this->add_parameter_rule($rules);
            return true;
        }
        // iterate over rules
        foreach ($rules as $rule)
        {
            switch ($class)
            {

                case 'org_openpsa_contacts_person_dba':
                case 'midgard_person':
                case 'org_openpsa_contacts_person':
                    $this->add_person_rule($rule);
                    break;

                case 'midgard_group':
                case 'org_openpsa_contacts_group_dba':
                case 'org_openpsa_contacts_group':
                    $this->add_group_rule($rule);
                    break;

                case 'midgard_member':
                case 'midgard_eventmember':
                    $this->add_misc_rule($rule , $class , 'uid');
                    break;

                case 'org_openpsa_campaign_member':
                case 'org_openpsa_campaign_message_receipt':
                case 'org_openpsa_link_log':
                    $this->add_misc_rule($rule , $class , 'person');
                    break;
                case 'org_maemo_devcodes_application':
                    $this->add_misc_rule($rule , $class , 'applicant');
                    break;
                case 'org_maemo_devcodes_code':
                    $this->add_misc_rule($rule , $class , 'recipient');
                    break;

                default:
                    debug_add("class " . $class . " not supported", MIDCOM_LOG_WARN);
                    break;
            }

        }
        debug_pop();
    }
    /**
     * Adds rule directly to the querybuilder
     * @param array $rule contains the rule
     */
    function add_person_rule($rule)
    {
        $this->_result_mc->add_constraint($rule['property'] , $rule['match'] , $rule['value']);
    }

    /**
     * Adds a group-rule to the querybuilder
     * @param array $rule contains the group-rule
     */
    function add_group_rule($rule)
    {
        //TODO: better way to preserve IN-Constraint on an empty array
        $member_array = array( 0 => -1);
        $group_member = array ( 0 => -1);

        $match = $rule['match'];
        $constraint_match = "IN";

        //check for match type- Needed to get persons who aren't a member of a group
        if($rule['match'] == '<>' || $rule['match'] == 'NOT LIKE')
        {
            $constraint_match = "NOT IN";
            switch($rule['match'])
            {
                case '<>':
                    $match = '=';
                    break;
                case 'NOT LIKE':
                    $match = 'LIKE';
                    break;
                default:
                    $match = '=';
                    break;
            }
        }
        $mc_group = new midgard_collector('midgard_member', 'sitegroup' , $_MIDGARD['sitegroup']);
        $mc_group->set_key_property('guid');
        $mc_group->add_constraint("gid.{$rule['property']}" , $match , $rule['value']);
        $mc_group->add_value_property('uid');

        $mc_group->execute();
        $keys = $mc_group->list_keys();
        foreach( $keys as $key => $value)
        {
            // get user-id
            $group_member[] = $mc_group->get_subkey($key , 'uid');
        }

        $this->_result_mc->add_constraint('id' , $constraint_match , $group_member);
    }

    /**
     * adds parameter rule to the querybuilder
     * @param array $rules array containing rules for the paramter
     */
    function add_parameter_rule($rules)
    {
        //get parents of wanted midgard_parameter
        $mc_parameter = new midgard_collector('midgard_parameter' , 'sitegroup' , $_MIDGARD['sitegroup']);
        $mc_parameter->set_key_property('id');
        $mc_parameter->add_value_property('parentguid');
        foreach($rules as $rule)
        {
            $mc_parameter->add_constraint($rule['property'] , $rule['match'] , $rule['value']);
        }
        $mc_parameter->execute();
        $parameter_keys = $mc_parameter->list_keys();

        if(count($parameter_keys) < 1)
        {
            //TODO: better solution for constraints leading to zero results
            //build constraint only if on 'LIKE' or '=' should be matched
            if($rule['match'] == 'LIKE' || $rule['match'] == '=')
            {
                $this->_result_mc->add_constraint('id' , '=' , -1);
            }
            return false;
        }
        //iterate over found parameters & call needed rule-functions
        foreach($parameter_keys as $parameter_key => $value)
        {
            $guid = $mc_parameter->get_subkey($parameter_key , 'parentguid');
            $parent = $_MIDCOM->dbfactory->get_object_by_guid($guid);

            switch (true)
            {
                case (   $_MIDCOM->dbfactory->is_a($parent, 'midgard_person')
                      || $_MIDCOM->dbfactory->is_a($parent, 'org_openpsa_contacts_person')
                      || $_MIDCOM->dbfactory->is_a($parent, 'org_openpsa_contacts_person_dba')):
                    $person_rule = array('property' => 'id' , 'match' => '=' , 'value' => $parent->id);
                    $this->add_person_rule($person_rule);
                    break;
                case ($_MIDCOM->dbfactory->is_a($parent, 'midgard_group')):
                    $group_rule = array('property' => 'id' , 'match' => '=' , 'value' => $parent->id);
                    $this->add_group_rule($group_rule);
                    break;
                case $_MIDCOM->dbfactory->is_a($parent, 'org_openpsa_campaign_member'):
                case $_MIDCOM->dbfactory->is_a($parent, 'org_openpsa_campaign_message_receipt'):
                case $_MIDCOM->dbfactory->is_a($parent, 'org_openpsa_link_log'):
                    $person_rule = array('property' => 'id' , 'match' => '=' , 'value' => $parent->person);
                    $this->add_person_rule($person_rule);
                    break;
                case $_MIDCOM->dbfactory->is_a($parent, 'org_maemo_devcodes_application'):
                    $person_rule = array('property' => 'id' , 'match' => '=' , 'value' => $parent->applicant);
                    $this->add_person_rule($person_rule);
                    break;
                case $_MIDCOM->dbfactory->is_a($parent, 'org_maemo_devcodes_code'):
                    $person_rule = array('property' => 'id' , 'match' => '=' , 'value' => $parent->recipient);
                    $this->add_person_rule($person_rule);
                    break;

                default:
                    debug_add("parameters for " . get_class($parent) . " -objects not supported (parent guid {$parent->guid}, param guid {$parent->guid})");
                    break;
            }
        }

        debug_pop();
    }
    /**
     * Adds a passed rule for the passed class to the querybuilder
     * @param array $rule contains the rule
     * @param string $class name of the class the rule will be added to
     * @param string $person_property contains the name of the property of the
     * passed class which links to the person
     */
    function add_misc_rule($rule , $class , $person_property)
    {
        $persons = array ( 0 => -1);
        $match = $rule['match'];
        $constraint_match = "IN";
        if($rule['match'] == '<>' || $rule['match'] == 'NOT LIKE')
        {
            $constraint_match = "NOT IN";
            switch($rule['match'])
            {
                case '<>':
                    $match = '=';
                    break;
                case 'NOT LIKE':
                    $match = 'LIKE';
                    break;
                default:
                    $match = '=';
                    break;
            }
        }
        $mc_misc = new midgard_collector($class , 'sitegroup' , $_MIDGARD['sitegroup']);
        $mc_misc->set_key_property('id');
        $mc_misc->add_constraint($rule['property'] , $match , $rule['value']);
        $mc_misc->add_value_property($person_property);

        $mc_misc->execute();

        $keys = $mc_misc->list_keys();

        foreach( $keys as $key => $value)
        {
            // get user-id
            $persons[] = $mc_misc->get_subkey($key , $person_property);
        }

        $this->_result_mc->add_constraint('id' , $constraint_match , $persons);
    }


}

?>