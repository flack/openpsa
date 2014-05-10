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
 * named 'classes' because there should never be two groups on this level using the same class
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_ruleresolver
{
    private $_rules = null; //Copy of rules as received
    private $_result_mc = null; // Contact-qb containing results

    public function __construct()
    {
        // if querybuilder is used response-time will increase -> set_key_property hast to be removed
        $this->_result_mc = org_openpsa_contacts_person_dba::new_collector('metadata.deleted', false);
    }

    /**
     * Recurses trough the rules array and creates QB instances & constraints as needed
     *
     * @param array $rules rules array
     * @return boolean indicating success/failure
     */
    function resolve(array $rules)
    {
        if (!array_key_exists('classes', $rules))
        {
            debug_add('rules[classes] is not defined', MIDCOM_LOG_ERROR);
            return false;
        }
        if (!is_array($rules['classes']))
        {
            debug_add('rules[classes] is not an array', MIDCOM_LOG_ERROR);
            return false;
        }
        if (!array_key_exists('groups', $rules))
        {
            debug_add('rules[groups] is not defined', MIDCOM_LOG_ERROR);
            return false;
        }
        $this->_rules = $rules;

        //start with first group
        $this->_result_mc->begin_group(strtoupper($rules['groups']));
        reset ($rules['classes']);
        //iterate over groups
        foreach ($rules['classes'] as $group)
        {
            $this->_resolve_rule_group($group);
        }
        $this->_result_mc->end_group();

        return true;
    }

    /**
     * Executes the collector instantiated via resolve, merges results and returns
     * single array of persons (or false in case of failure)
     */
    function execute()
    {
        $this->_result_mc->add_value_property('lastname');
        $this->_result_mc->add_value_property('firstname');
        $this->_result_mc->add_value_property('email');
        $this->_result_mc->add_value_property('id');
        $this->_result_mc->add_order('lastname', 'ASC');
        $this->_result_mc->execute();
        $results = $this->_result_mc->list_keys();
        $ret = array();
        foreach ($results as $key => $value)
        {
            $ret[$this->_result_mc->get_subkey($key, 'id')] = array
            (
                'lastname' => $this->_result_mc->get_subkey($key, 'lastname'),
                'firstname' => $this->_result_mc->get_subkey($key, 'firstname'),
                'email' => $this->_result_mc->get_subkey($key, 'email'),
                'guid' => $key,
            );
        }

        return $ret;
    }

    /**
     * Resolves the rules in a single rule group
     *
     * @param array $group single group from rules array
     * @param string $match_class wanted class in group
     * @return boolean indicating success/failure
     */
    private function _resolve_rule_group(array $group, $match_class = false)
    {
        if (   $match_class
            && $group['class'] != $match_class)
        {
            debug_add("{$group['class']} != {$match_class}, unmatched classes where match required", MIDCOM_LOG_ERROR);
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
                return false;
            }
            if (!is_array($group['rules']))
            {
                debug_add('group[rules] is not an array', MIDCOM_LOG_ERROR);
                return false;
            }
            $this->add_rules($group['rules'], $group['class']);
        }

        return true;
    }

    /**
     * Iterates over passed rules for given class and calls functions
     * to add rules to the querybuilder/collector
     *
     * @param array $rules array containing rules
     * @param string $class containing name of class for the rules
     */
    function add_rules(array $rules, $class)
    {
        debug_add("try to build rules for class: {$class}");

        //special case parameters - uses 3 rules standard
        if ($class == 'midgard_parameter')
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
                    $this->add_misc_rule($rule, $class, 'uid');
                    break;

                case 'org_openpsa_campaign_member':
                case 'org_openpsa_campaign_message_receipt':
                case 'org_openpsa_link_log':
                    $this->add_misc_rule($rule, $class, 'person');
                    break;

                default:
                    debug_add("class " . $class . " not supported", MIDCOM_LOG_WARN);
                    break;
            }
        }
    }

    /**
     * Adds rule directly to the querybuilder
     *
     * @param array $rule contains the rule
     */
    function add_person_rule(array $rule)
    {
        $this->_result_mc->add_constraint($rule['property'], $rule['match'], $rule['value']);
    }

    /**
     * Adds a group-rule to the querybuilder
     *
     * @param array $rule contains the group-rule
     */
    function add_group_rule(array $rule)
    {
        //TODO: better way to preserve IN-Constraint on an empty array
        $group_member = array ( 0 => -1);

        $match = $rule['match'];
        $constraint_match = "IN";

        //check for match type- Needed to get persons who aren't a member of a group
        if (   $rule['match'] == '<>'
            || $rule['match'] == 'NOT LIKE')
        {
            $constraint_match = "NOT IN";
            switch ($rule['match'])
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
        $mc_group = new midgard_collector('midgard_member', 'metadata.deleted', false);
        $mc_group->set_key_property('guid');
        $mc_group->add_constraint("gid.{$rule['property']}", $match, $rule['value']);
        $mc_group->add_value_property('uid');

        $mc_group->execute();
        $keys = $mc_group->list_keys();
        foreach ($keys as $key => $value)
        {
            // get user-id
            $group_member[] = $mc_group->get_subkey($key, 'uid');
        }

        $this->_result_mc->add_constraint('id', $constraint_match, $group_member);
    }

    /**
     * Adds parameter rule to the querybuilder
     *
     * @param array $rules array containing rules for the paramter
     */
    function add_parameter_rule(array $rules)
    {
        //get parents of wanted midgard_parameter
        $mc_parameter = new midgard_collector('midgard_parameter', 'metadata.deleted', false);
        $mc_parameter->set_key_property('id');
        $mc_parameter->add_value_property('parentguid');
        foreach ($rules as $rule)
        {
            $mc_parameter->add_constraint($rule['property'], $rule['match'], $rule['value']);
        }
        $mc_parameter->execute();
        $parameter_keys = $mc_parameter->list_keys();

        if (count($parameter_keys) < 1)
        {
            //TODO: better solution for constraints leading to zero results
            //build constraint only if on 'LIKE' or '=' should be matched
            if ($rule['match'] == 'LIKE' || $rule['match'] == '=')
            {
                $this->_result_mc->add_constraint('id', '=', -1);
            }
            return false;
        }
        //iterate over found parameters & call needed rule-functions
        foreach ($parameter_keys as $parameter_key => $value)
        {
            $guid = $mc_parameter->get_subkey($parameter_key, 'parentguid');
            try
            {
                $parent = midcom::get('dbfactory')->get_object_by_guid($guid);
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }
            switch (true)
            {
                case (is_a($parent, 'midcom_db_person')):
                    $person_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->id);
                    $this->add_person_rule($person_rule);
                    break;
                case (midcom::get('dbfactory')->is_a($parent, 'midgard_group')):
                    $group_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->id);
                    $this->add_group_rule($group_rule);
                    break;
                case midcom::get('dbfactory')->is_a($parent, 'org_openpsa_campaign_member'):
                case midcom::get('dbfactory')->is_a($parent, 'org_openpsa_campaign_message_receipt'):
                case midcom::get('dbfactory')->is_a($parent, 'org_openpsa_link_log'):
                    $person_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->person);
                    $this->add_person_rule($person_rule);
                    break;

                default:
                    debug_add("parameters for " . get_class($parent) . " -objects not supported (parent guid {$parent->guid}, param guid {$parent->guid})");
                    break;
            }
        }
    }

    /**
     * Adds a passed rule for the passed class to the querybuilder
     *
     * @param array $rule contains the rule
     * @param string $class name of the class the rule will be added to
     * @param string $person_property contains the name of the property of the
     * passed class which links to the person
     */
    function add_misc_rule(array $rule, $class, $person_property)
    {
        $persons = array ( 0 => -1);
        $match = $rule['match'];
        $constraint_match = "IN";
        if ($rule['match'] == '<>' || $rule['match'] == 'NOT LIKE')
        {
            $constraint_match = "NOT IN";
            switch ($rule['match'])
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
        $mc_misc = new midgard_collector($class, 'metadata.deleted', false);
        $mc_misc->set_key_property('id');
        $mc_misc->add_constraint($rule['property'], $match, $rule['value']);
        $mc_misc->add_value_property($person_property);

        $mc_misc->execute();

        $keys = $mc_misc->list_keys();

        foreach ($keys as $key => $value)
        {
            // get user-id
            $persons[] = $mc_misc->get_subkey($key, $person_property);
        }

        $this->_result_mc->add_constraint('id', $constraint_match, $persons);
    }

    /**
     * List object's properties for JS rule builder
     *
     * PONDER: Should we support schema somehow (only for non-parameter keys), this would practically require manual parsing...
     *
     * @param midcom_core_dbaobject $object
     * @param midcom_services_i18n_l10n $l10n
     */
    public static function list_object_properties($object, midcom_services_i18n_l10n $l10n)
    {
        // These are internal to midgard and/or not valid QB constraints
        $skip_properties = array();
        // These will be deprecated soon
        $skip_properties[] = 'orgOpenpsaAccesstype';
        $skip_properties[] = 'orgOpenpsaWgtype';

        if (midcom::get('dbfactory')->is_a($object, 'org_openpsa_person'))
        {
            // The info field is a special case
            $skip_properties[] = 'info';
            // These legacy fields are rarely used
            $skip_properties[] = 'topic';
            $skip_properties[] = 'subtopic';
            $skip_properties[] = 'office';
            // This makes very little sense as a constraint
            $skip_properties[] = 'img';
            // Duh
            $skip_properties[] = 'password';
        }
        if (midcom::get('dbfactory')->is_a($object, 'midgard_member'))
        {
            // The info field is a special case
            $skip_properties[] = 'info';
        }
        // Skip metadata for now
        $skip_properties[] = 'metadata';
        $ret = array();
        while (list ($property, $value) = each($object))
        {
            if (   preg_match('/^_/', $property)
                || in_array($property, $skip_properties))
            {
                // Skip private or otherwise invalid properties
                continue;
            }
            if (   is_object($value)
                && !is_a($value, 'midgard_datetime'))
            {
                while (list ($property2, $value2) = each($value))
                {
                    $prop_merged = "{$property}.{$property2}";
                    $ret[$prop_merged] = $l10n->get("property:{$prop_merged}");
                }
            }
            else
            {
                $ret[$property] = $l10n->get("property:{$property}");
            }
        }
        asort($ret);
        return $ret;
    }
}
?>