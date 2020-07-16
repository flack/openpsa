<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\helper;

use midcom;
use midcom_connection;
use midcom_core_query;
use midcom_core_account;
use midcom_db_person;
use midcom_error;
use midcom_helper_reflector;
use midgard_reflection_property;
use midcom_baseclasses_components_configuration;

/**
 * Experimental autocomplete helper
 */
class autocomplete
{
    /**
     * The request data we're working on
     *
     * @var array
     */
    private $request;

    public function __construct(array $data)
    {
        $this->request = $data;
        $this->verify_request();
    }

    private function verify_request()
    {
        if (!class_exists($this->request['class'])) {
            throw new midcom_error("Class {$this->request['class']} could not be loaded");
        }

        if (empty($this->request['searchfields'])) {
            throw new midcom_error("No fields to search for defined");
        }

        if (empty($this->request["term"])) {
            throw new midcom_error("Empty query string.");
        }

        if (!isset($this->request['titlefield'])) {
            $this->request['titlefield'] = null;
        }
        if (!isset($this->request['result_headers'])) {
            $this->request['result_headers'] = [];
        }
    }

    private function prepare_qb()
    {
        $qb = call_user_func([$this->request['class'], 'new_query_builder']);

        if (!empty($this->request['constraints'])) {
            $this->apply_constraints($qb, $this->request['constraints']);
        }

        $constraints = $this->get_search_constraints();
        if (!empty($constraints)) {
            $qb->begin_group('OR');
            $this->apply_constraints($qb, $constraints);
            $qb->end_group();
        }

        if (!empty($this->request['orders'])) {
            ksort($this->request['orders']);
            foreach ($this->request['orders'] as $data) {
                foreach ($data as $field => $order) {
                    $qb->add_order($field, $order);
                }
            }
        }
        return $qb;
    }

    private function apply_constraints(midcom_core_query $query, array $constraints)
    {
        ksort($constraints);
        foreach ($constraints as $key => $data) {
            if (   !array_key_exists('value', $data)
                || empty($data['field'])
                || empty($data['op'])) {
                debug_add("Constraint #{$key} is not correctly defined, skipping", MIDCOM_LOG_WARN);
                continue;
            }
            if ($data['field'] === 'username') {
                midcom_core_account::add_username_constraint($query, $data['op'], $data['value']);
            } else {
                $query->add_constraint($data['field'], $data['op'], $data['value']);
            }
        }
    }

    private function get_search_constraints() : array
    {
        $constraints = [];
        $query = $this->request["term"];
        if (preg_match('/^%+$/', $query)) {
            debug_add('query is all wildcards, don\'t waste time in adding LIKE constraints');
            return $constraints;
        }

        $reflector = new midgard_reflection_property(midcom_helper_reflector::resolve_baseclass($this->request['class']));

        foreach ($this->request['searchfields'] as $field) {
            $field_type = $reflector->get_midgard_type($field);
            $operator = 'LIKE';
            if (str_contains($field, '.')) {
                //TODO: This should be resolved properly
                $field_type = MGD_TYPE_STRING;
            }
            switch ($field_type) {
                case MGD_TYPE_GUID:
                case MGD_TYPE_STRING:
                case MGD_TYPE_LONGTEXT:
                    $query = $this->get_querystring();
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                case MGD_TYPE_FLOAT:
                    $operator = '=';
                    break;
                default:
                    debug_add("can't handle field type " . $field_type, MIDCOM_LOG_WARN);
                    continue 2;
            }
            debug_add("adding search (ORed) constraint: {$field} {$operator} '{$query}'");
            $constraints[] = [
                'field' => $field,
                'op' => $operator,
                'value' => $query
            ];
        }
        return $constraints;
    }

    public function get_querystring() : string
    {
        $query = $this->request["term"];
        $wildcard_query = $query;
        if (   isset($this->request['auto_wildcards'])
            && !str_contains($query, '%')) {
            switch ($this->request['auto_wildcards']) {
                case 'start':
                    $wildcard_query = '%' . $query;
                    break;
                case 'end':
                    $wildcard_query = $query . '%';
                    break;
                case 'both':
                    $wildcard_query = '%' . $query . '%';
                    break;
                default:
                    debug_add("Don't know how to handle auto_wildcards value '" . $this->request['auto_wildcards'] . "'", MIDCOM_LOG_WARN);
                    break;
            }
        }
        $wildcard_query = str_replace("*", "%", $wildcard_query);
        $wildcard_query = preg_replace('/%+/', '%', $wildcard_query);
        return $wildcard_query;
    }

    public function get_objects() : array
    {
        return $this->prepare_qb()->execute();
    }

    public function get_results() : array
    {
        if (empty($this->request["id_field"])) {
            throw new midcom_error("Empty ID field.");
        }

        $results = $this->get_objects();
        $items = [];

        foreach ($results as $object) {
            $item = [
                'id' => $object->{$this->request['id_field']},
                'label' => self::create_item_label($object, $this->request['result_headers'], $this->request['titlefield']),
            ];
            if (!empty($this->request['result_headers'])) {
                $item['description'] = self::build_label($object, array_column($this->request['result_headers'], 'name'));
            }
            if (!empty($this->request['categorize_by_parent_label'])) {
                $item['category'] = '';
                if ($parent = $object->get_parent()) {
                    $item['category'] = midcom_helper_reflector::get($parent)->get_object_label($parent);
                }
            }
            $item['value'] = $item['label'];

            $items[] = $item;
        }
        usort($items, [$this, 'sort_items']);

        return $items;
    }

    public static function sort_items($a, $b)
    {
        if (isset($a['category'])) {
            $cmp = strnatcasecmp($a['category'], $b['category']);
            if ($cmp != 0) {
                return $cmp;
            }
        }
        return strnatcasecmp($a['label'], $b['label']);
    }

    public static function add_head_elements($creation_mode_enabled = false, $sortable = false)
    {
        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/autocomplete.css');

        $components = ['menu', 'autocomplete'];
        if ($sortable) {
            $components[] = 'mouse';
            $components[] = 'sortable';
        }
        if ($creation_mode_enabled) {
            $components = array_merge($components, ['mouse', 'draggable', 'resizable', 'button', 'dialog']);
            $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/workflow.js');
        }
        $head->enable_jquery_ui($components);
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/autocomplete.js');
    }

    public static function get_widget_config($type) : array
    {
        $handler_url = midcom_connection::get_url('self') . 'midcom-exec-midcom.datamanager/autocomplete.php';

        $widget_config = midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config')->get('clever_classes');
        $config = $widget_config[$type];
        $config['handler_url'] = $handler_url;
        return $config;
    }

    public static function create_item_label($object, $result_headers, $titlefield) : string
    {
        $label = [];
        if (!empty($titlefield)) {
            if ($label = self::build_label($object, (array) $titlefield)) {
                return $label;
            }
        }
        if ($label = midcom_helper_reflector::get($object)->get_object_label($object)) {
            return $label;
        }
        if ($label = self::build_label($object, array_column($result_headers, 'name'))) {
            return $label;
        }

        return get_class($object) . ' #' . $object->id;
    }

    private static function build_label($object, array $fields) : string
    {
        $label = [];
        foreach ((array) $fields as $field) {
            if ($value = self::get_property_string($object, $field)) {
                $label[] = $value;
            }
        }
        return implode(', ', $label);
    }

    private static function get_property_string($object, string $field) : string
    {
        if (preg_match('/^metadata\.(.+)$/', $field, $regs)) {
            $date_fields = ['created', 'revised', 'published', 'schedulestart', 'scheduleend', 'imported', 'exported', 'approved'];
            $person_fields = ['creator', 'revisor', 'approver', 'locker'];
            $metadata_property = $regs[1];
            $value = $object->metadata->$metadata_property;

            if (in_array($metadata_property, $date_fields)) {
                return $value ? strftime('%x %X', $value) : '';
            }
            if (in_array($metadata_property, $person_fields)) {
                if ($value) {
                    $person = new midcom_db_person($value);
                    return self::sanitize_label($person->name);
                }
                return '';
            }
            return self::sanitize_label($value);
        }
        if (   $field == 'username'
            && $object instanceof midcom_db_person) {
            $account = new midcom_core_account($object);
            return self::sanitize_label($account->get_username());
        }
        return self::sanitize_label($object->$field);
    }

    private static function sanitize_label($input) : string
    {
        return trim(strip_tags((string) $input));
    }
}