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
 * [
 *    'type' => 'AND',
 *    'classes' => [
 *        [
 *            'type' => 'OR',
 *            'class' => 'org_openpsa_contacts_person_dba',
 *            'rules' => [
 *                [
 *                    'property' => 'email',
 *                    'match' => 'LIKE',
 *                    'value' => '%@%'
 *                ],
 *                [
 *                    'property' => 'handphone',
 *                    'match' => '<>',
 *                    'value' => ''
 *                ],
 *            ],
 *        ],
 *        [
 *            'type' => 'AND',
 *            'class' => 'midgard_parameter',
 *            'rules' => [
 *                [
 *                    'property' => 'domain',
 *                    'match' => '=',
 *                    'value' => 'openpsa_test'
 *                ],
 *                [
 *                    'property' => 'name',
 *                    'match' => '=',
 *                    'value' => 'param_match'
 *                ],
 *                [
 *                    'property' => 'value',
 *                    'match' => '=',
 *                    'value' => 'bar'
 *                ],
 *            ],
 *        ],
 *    ],
 * ],
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
    /**
     * Contact-qb containing results
     */
    private midcom_core_collector $mc;

    public function __construct()
    {
        // if querybuilder is used response-time will increase -> set_key_property has to be removed
        $this->mc = org_openpsa_contacts_person_dba::new_collector();
    }

    public function get_mc() : midcom_core_collector
    {
        return $this->mc;
    }

    public static function parse(string $ruleset) : array
    {
        $rules = json_decode($ruleset, true);

        if (empty($rules)) {
            throw new midcom_error('given ruleset is empty');
        }
        return $rules;
    }

    /**
     * Recurses trough the rules array and creates QB instances & constraints as needed
     */
    public function resolve(array $rules) : bool
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
        try {
            $this->process_classes($rules['classes'], $rules['type']);
            return true;
        } catch (midcom_error $e) {
            $e->log();
            return false;
        }
    }

    /**
     * Executes the collector instantiated via resolve, merges results
     *
     * @return array Person data
     */
    public function execute() : array
    {
        $this->mc->add_order('lastname', 'ASC');
        return $this->mc->get_rows(['lastname', 'firstname', 'email', 'guid'], 'id');
    }

    private function process_classes(array $classes, string $type)
    {
        $this->mc->begin_group(strtoupper($type));
        foreach ($classes as $group) {
            $this->resolve_rule_group($group);
        }
        $this->mc->end_group();
    }

    /**
     * Resolves the rules in a single rule group
     */
    private function resolve_rule_group(array $group)
    {
        //check if rule is a group
        if (array_key_exists('groups', $group)) {
            $this->process_classes($group['classes'], $group['groups']);
            return;
        }
        if (!array_key_exists('rules', $group) || !is_array($group['rules'])) {
            throw new midcom_error('group[rules] is not an array');
        }
        $this->add_rules($group['rules'], $group['class']);
    }

    /**
     * Iterates over passed rules for given class and calls functions
     * to add rules to the querybuilder/collector
     */
    private function add_rules(array $rules, string $class)
    {
        if (midcom::get()->dbclassloader->is_midcom_db_object($class)) {
            $class = midcom::get()->dbclassloader->get_mgdschema_class_name_for_midcom_class($class);
        }

        match ($class) {
            'midgard_parameter' => $this->add_parameter_rule($rules),
            midcom::get()->config->get('person_class'),
            'midgard_person',
            'org_openpsa_person' => $this->apply_rules('person', $rules),
            'midgard_group',
            'org_openpsa_organization' => $this->apply_rules('group', $rules),
            'midgard_member',
            'org_openpsa_eventmember' => $this->apply_rules('misc', $rules, $class, 'uid'),
            'org_openpsa_campaign_member',
            'org_openpsa_campaign_message_receipt',
            'org_openpsa_link_log' => $this->apply_rules('misc', $rules, $class, 'person')
        };
    }

    private function apply_rules(string $type, array $rules, ...$args)
    {
        $func = 'add_' . $type . '_rule';
        foreach ($rules as $rule) {
            $this->$func($rule, ...$args);
        }
    }

    /**
     * Adds rule directly to the querybuilder
     */
    private function add_person_rule(array $rule)
    {
        if ($rule['property'] === 'username') {
            midcom_core_account::add_username_constraint($this->mc, $rule['match'], $rule['value']);
        } else {
            $this->mc->add_constraint($rule['property'], $rule['match'], $rule['value']);
        }
    }

    /**
     * Adds parameter rule to the querybuilder
     */
    private function add_parameter_rule(array $rules)
    {
        //get parents of wanted midgard_parameter
        $mc_parameter = new midgard_collector('midgard_parameter');
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
            if (in_array($rule['match'], ['LIKE', '='])) {
                $this->mc->add_constraint('id', '=', -1);
            }
            return;
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
                case $parent instanceof midcom_db_person:
                    $person_rule = ['property' => 'id', 'match' => '=', 'value' => $parent->id];
                    $this->add_person_rule($person_rule);
                    break;

                case (midcom::get()->dbfactory->is_a($parent, 'midgard_group')):
                    $group_rule = ['property' => 'id', 'match' => '=', 'value' => $parent->id];
                    $this->add_group_rule($group_rule);
                    break;

                case $parent instanceof org_openpsa_directmarketing_campaign_member_dba:
                case $parent instanceof org_openpsa_directmarketing_campaign_messagereceipt_dba:
                case $parent instanceof org_openpsa_directmarketing_link_log_dba:
                    $this->add_person_rule(['property' => 'id', 'match' => '=', 'value' => $parent->person]);
                    break;

                default:
                    throw new midcom_error("parameters for " . $parent::class . " -objects not supported (parent guid {$parent->guid})");
            }
        }
    }

    /**
     * Adds a group-rule to the querybuilder
     */
    private function add_group_rule(array $rule)
    {
        $rule['property'] = 'gid.' . $rule['property'];
        $this->add_misc_rule($rule, 'midgard_member', 'uid');
    }

    /**
     * Adds a passed rule for the passed class to the querybuilder
     *
     * @param string $class name of the class the rule will be added to
     * @param string $person_property contains the name of the property of the
     * passed class which links to the person
     */
    private function add_misc_rule(array $rule, string $class, string $person_property)
    {
        $persons = [ 0 => -1];
        $match = $rule['match'];
        $constraint_match = "IN";
        if ($rule['match'] == '<>') {
            $constraint_match = "NOT IN";
            $match = '=';
        } elseif ($rule['match'] == 'NOT LIKE') {
            $constraint_match = "NOT IN";
            $match = 'LIKE';
        }

        $mc_misc = new midgard_collector($class);
        $mc_misc->set_key_property('id');
        $mc_misc->add_constraint($rule['property'], $match, $rule['value']);
        $mc_misc->add_value_property($person_property);

        $mc_misc->execute();
        $keys = $mc_misc->list_keys();

        foreach (array_keys($keys) as $key) {
            // get user-id
            $persons[] = $mc_misc->get_subkey($key, $person_property);
        }

        $this->mc->add_constraint('id', $constraint_match, $persons);
    }

    public static function build_property_map(midcom_services_i18n_l10n $l10n) : array
    {
        $types = [
            'person' => new org_openpsa_contacts_person_dba,
            'group' => new org_openpsa_contacts_group_dba,
            'membership' => new org_openpsa_contacts_member_dba
        ];
        $return = [];
        foreach ($types as $name => $object) {
            $return[$name] = [
                'properties' => self::list_object_properties($object, $l10n),
                'localized' => $l10n->get('class:' . $name),
                'parameters' => false
            ];
        }
        $return['generic_parameters'] = [
            'properties' => false,
            'localized' => $l10n->get('class:generic parameters'),
            'parameters' => true
        ];

        return $return;
    }

    /**
     * List object's properties for JS rule builder
     *
     * PONDER: Should we support schema somehow (only for non-parameter keys), this would practically require manual parsing...
     */
    public static function list_object_properties(midcom_core_dbaobject $object, midcom_services_i18n_l10n $l10n) : array
    {
        // These are internal to midgard and/or not valid QB constraints
        $skip_properties = [
            // These will be deprecated soon
            // Skip metadata for now
            'metadata'
        ];

        if (midcom::get()->dbfactory->is_a($object, 'midgard_member')) {
            // The info field is a special case
            $skip_properties[] = 'info';
        }
        $ret = [];

        foreach ($object->get_properties() as $property) {
            if (   $property[0] == '_'
                || in_array($property, $skip_properties)) {
                // Skip private or otherwise invalid properties
                continue;
            }
            $ret[$property] = $l10n->get("property:{$property}");
        }
        asort($ret);
        return $ret;
    }
}
