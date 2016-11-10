<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midgard\introspection\helper;

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
    private $rules = array(); //Copy of rules as received
    private $mc; // Contact-qb containing results

    public function __construct()
    {
        // if querybuilder is used response-time will increase -> set_key_property has to be removed
        $this->mc = org_openpsa_contacts_person_dba::new_collector('metadata.deleted', false);
    }

    /**
     * @return midcom_core_collector
     */
    public function get_mc()
    {
        return $this->mc;
    }

    /**
     *
     * @param string $ruleset
     * @throws midcom_error
     * @return array
     */
    public static function parse($ruleset)
    {
        $eval_ret = @eval('$rules = ' . $ruleset . ';');

        if (   $eval_ret === false
            || !is_array($rules)) {
            throw new midcom_error('given ruleset could not be parsed');
        }
        if (count($rules) == 0) {
            throw new midcom_error('given ruleset is empty');
        }
        return $rules;
    }

    /**
     * Recurses trough the rules array and creates QB instances & constraints as needed
     *
     * @param array $rules rules array
     * @return boolean indicating success/failure
     */
    public function resolve(array $rules)
    {
        if (!array_key_exists('classes', $rules)) {
            debug_add('rules[classes] is not defined', MIDCOM_LOG_ERROR);
            return false;
        }
        if (!is_array($rules['classes'])) {
            debug_add('rules[classes] is not an array', MIDCOM_LOG_ERROR);
            return false;
        }
        if (!array_key_exists('type', $rules)) {
            debug_add('rules[type] is not defined', MIDCOM_LOG_ERROR);
            return false;
        }
        $this->rules = $rules;
        $stat = true;
        //start with first group
        $this->mc->begin_group(strtoupper($rules['type']));
        reset ($rules['classes']);
        //iterate over groups
        foreach ($rules['classes'] as $group) {
            $stat = $this->resolve_rule_group($group);
            if (!$stat) {
                break;
            }
        }
        $this->mc->end_group();

        return $stat;
    }

    /**
     * Executes the collector instantiated via resolve, merges results
     *
     * @return array Person data
     */
    public function execute()
    {
        $this->mc->add_order('lastname', 'ASC');
        return $this->mc->get_rows(array('lastname', 'firstname', 'email', 'guid'), 'id');
    }

    /**
     * Resolves the rules in a single rule group
     *
     * @param array $group single group from rules array
     * @return boolean indicating success/failure
     */
    private function resolve_rule_group(array $group)
    {
        //check if rule is a group
        if (array_key_exists('groups', $group)) {
            $this->mc->begin_group(strtoupper($group['groups']));
            foreach ($group['classes'] as $subgroup) {
                $this->resolve_rule_group($subgroup);
            }
            $this->mc->end_group();
            return true;
        }
        if (!array_key_exists('rules', $group)) {
            debug_add('group[rules] is not defined', MIDCOM_LOG_ERROR);
            return false;
        }
        if (!is_array($group['rules'])) {
            debug_add('group[rules] is not an array', MIDCOM_LOG_ERROR);
            return false;
        }
        return $this->add_rules($group['rules'], $group['class']);
    }

    /**
     * Iterates over passed rules for given class and calls functions
     * to add rules to the querybuilder/collector
     *
     * @param array $rules array containing rules
     * @param string $class containing name of class for the rules
     */
    private function add_rules(array $rules, $class)
    {
        $class = midcom::get()->dbclassloader->get_mgdschema_class_name_for_midcom_class($class);
        //special case parameters - uses 3 rules standard
        if ($class == 'midgard_parameter') {
            return $this->add_parameter_rule($rules);
        }
        // iterate over rules
        foreach ($rules as $rule) {
            switch ($class) {
                case midcom::get()->config->get('person_class'):
                case 'midgard_person':
                case 'org_openpsa_person':
                    return $this->add_person_rule($rule);

                case 'midgard_group':
                case 'org_openpsa_organization':
                    return $this->add_group_rule($rule);

                case 'midgard_member':
                case 'midgard_eventmember':
                    return $this->add_misc_rule($rule, $class, 'uid');

                case 'org_openpsa_campaign_member':
                case 'org_openpsa_campaign_message_receipt':
                case 'org_openpsa_link_log':
                    return $this->add_misc_rule($rule, $class, 'person');

                default:
                    debug_add("class " . $class . " not supported", MIDCOM_LOG_WARN);
                    return false;
            }
        }
    }

    /**
     * Adds rule directly to the querybuilder
     *
     * @param array $rule contains the rule
     */
    private function add_person_rule(array $rule)
    {
        return $this->mc->add_constraint($rule['property'], $rule['match'], $rule['value']);
    }

    /**
     * Adds parameter rule to the querybuilder
     *
     * @param array $rules array containing rules for the parameter
     */
    private function add_parameter_rule(array $rules)
    {
        //get parents of wanted midgard_parameter
        $mc_parameter = new midgard_collector('midgard_parameter', 'metadata.deleted', false);
        $mc_parameter->set_key_property('id');
        $mc_parameter->add_value_property('parentguid');
        foreach ($rules as $rule) {
            $mc_parameter->add_constraint($rule['property'], $rule['match'], $rule['value']);
        }
        $mc_parameter->execute();
        $parameter_keys = $mc_parameter->list_keys();

        if (count($parameter_keys) < 1) {
            //TODO: better solution for constraints leading to zero results
            //build constraint only if on 'LIKE' or '=' should be matched
            if ($rule['match'] == 'LIKE' || $rule['match'] == '=') {
                return $this->mc->add_constraint('id', '=', -1);
            }
            return true;
        }
        //iterate over found parameters & call needed rule-functions
        foreach (array_keys($parameter_keys) as $parameter_key) {
            $guid = $mc_parameter->get_subkey($parameter_key, 'parentguid');
            try {
                $parent = midcom::get()->dbfactory->get_object_by_guid($guid);
            } catch (midcom_error $e) {
                $e->log();
                continue;
            }
            switch (true) {
                case (is_a($parent, 'midcom_db_person')):
                    $person_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->id);
                    return $this->add_person_rule($person_rule);

                case (midcom::get()->dbfactory->is_a($parent, 'midgard_group')):
                    $group_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->id);
                    return $this->add_group_rule($group_rule);

                case midcom::get()->dbfactory->is_a($parent, 'org_openpsa_campaign_member'):
                case midcom::get()->dbfactory->is_a($parent, 'org_openpsa_campaign_message_receipt'):
                case midcom::get()->dbfactory->is_a($parent, 'org_openpsa_link_log'):
                    $person_rule = array('property' => 'id', 'match' => '=', 'value' => $parent->person);
                    return $this->add_person_rule($person_rule);

                default:
                    debug_add("parameters for " . get_class($parent) . " -objects not supported (parent guid {$parent->guid}, param guid {$parent->guid})");
                    return false;
            }
        }
    }

    /**
     * Adds a group-rule to the querybuilder
     *
     * @param array $rule contains the group-rule
     */
    private function add_group_rule(array $rule)
    {
        $rule['property'] = 'gid.' . $rule['property'];
        return $this->add_misc_rule($rule, 'midgard_member', 'uid');
    }

    /**
     * Adds a passed rule for the passed class to the querybuilder
     *
     * @param array $rule contains the rule
     * @param string $class name of the class the rule will be added to
     * @param string $person_property contains the name of the property of the
     * passed class which links to the person
     */
    private function add_misc_rule(array $rule, $class, $person_property)
    {
        $persons = array ( 0 => -1);
        $match = $rule['match'];
        $constraint_match = "IN";
        if ($rule['match'] == '<>') {
            $constraint_match = "NOT IN";
            $match = '=';
        } elseif ($rule['match'] == 'NOT LIKE') {
            $constraint_match = "NOT IN";
            $match = 'LIKE';
        }

        $mc_misc = new midgard_collector($class, 'metadata.deleted', false);
        $mc_misc->set_key_property('id');
        $mc_misc->add_constraint($rule['property'], $match, $rule['value']);
        $mc_misc->add_value_property($person_property);

        $mc_misc->execute();
        $keys = $mc_misc->list_keys();

        foreach (array_keys($keys) as $key) {
            // get user-id
            $persons[] = $mc_misc->get_subkey($key, $person_property);
        }

        return $this->mc->add_constraint('id', $constraint_match, $persons);
    }

    public static function build_property_map(midcom_services_i18n_l10n $l10n)
    {
        $types = array
        (
            'person' => new org_openpsa_contacts_person_dba,
            'group' => new org_openpsa_contacts_group_dba,
            'membership' => new org_openpsa_contacts_member_dba
        );
        $return = array();
        foreach ($types as $name => $object) {
            $return[$name] = array
            (
                'properties' => self::list_object_properties($object, $l10n),
                'localized' => $l10n->get('class:' . $name),
                'parameters' => false
            );
        }
        $return['generic_parameters'] = array
        (
            'properties' => false,
            'localized' => $l10n->get('class:generic parameters'),
            'parameters' => true
        );

        return $return;
    }

    /**
     * List object's properties for JS rule builder
     *
     * PONDER: Should we support schema somehow (only for non-parameter keys), this would practically require manual parsing...
     *
     * @param midcom_core_dbaobject $object
     * @param midcom_services_i18n_l10n $l10n
     */
    public static function list_object_properties(midcom_core_dbaobject $object, midcom_services_i18n_l10n $l10n)
    {
        // These are internal to midgard and/or not valid QB constraints
        $skip_properties = array
        (
            // These will be deprecated soon
            'orgOpenpsaAccesstype',
            'orgOpenpsaWgtype',
            // Skip metadata for now
            'metadata'
        );

        if (midcom::get()->dbfactory->is_a($object, 'org_openpsa_person')) {
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
        if (midcom::get()->dbfactory->is_a($object, 'midgard_member')) {
            // The info field is a special case
            $skip_properties[] = 'info';
        }
        $ret = array();
        $helper = new helper;

        foreach ($helper->get_object_vars($object) as $property => $value) {
            if (   preg_match('/^_/', $property)
                || in_array($property, $skip_properties)
                || property_exists($object, $property)) {
                // Skip private or otherwise invalid properties
                continue;
            }
            if (   is_object($value)
                && !is_a($value, 'midgard_datetime')) {
                while (list ($property2, $value2) = each($value)) {
                    $prop_merged = "{$property}.{$property2}";
                    $ret[$prop_merged] = $l10n->get("property:{$prop_merged}");
                }
            } else {
                $ret[$property] = $l10n->get("property:{$property}");
            }
        }
        asort($ret);
        return $ret;
    }
}
