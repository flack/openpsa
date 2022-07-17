<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom;
use midcom_error;
use midcom_connection;
use midcom_core_dbaobject;

/**
 * Experimental storage class
 */
class mnrelation extends delayed
{
    public function __construct(midcom_core_dbaobject $object, array $config)
    {
        parent::__construct($object, $config);
        $defaults = [
            'sortable' => false,
            'allow_multiple' => true,
            'mapping_class_name' => null,
            'master_fieldname' => null,
            'member_fieldname' => null,
            'master_is_id' => false,
            'constraints' => [],
            'require_corresponding_option' => false,
            'sortable_sort_order' => 'DESC',
            'additional_fields' => [],
        ];
        $this->config['type_config'] = array_merge($defaults, $this->config['type_config']);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $selection = array_flip((array) $this->value);
        $existing = $this->load_objects();
        $new = array_diff_key($selection, $existing);
        $delete = array_diff_key($existing, $selection);

        foreach (array_keys($new) as $key) {
            $this->create_relation($key, $this->get_score($key, $selection));
        }

        foreach ($delete as $key => $member) {
            if (!$member->delete()) {
                throw new midcom_error("Failed to delete member record for key {$key}: " . midcom_connection::get_error_string());
            }
        }
        if (!empty($this->config['type_config']['sortable'])) {
            foreach ($existing as $key => $member) {
                if (array_key_exists($key, $selection)) {
                    $score = $this->get_score($key, $selection);
                    if ($member->metadata->score != $score) {
                        $member->metadata->score = $score;
                        if (!$member->update()) {
                            throw new midcom_error("Failed to update member record for key {$key}: " . midcom_connection::get_error_string());
                        }
                    }
                }
            }
        }
    }

    private function get_score($key, array $selection) : int
    {
        // if the sort order is descending, the first element needs the highest score
        if ($this->config['type_config']['sortable'] == 'DESC') {
            return abs($selection[$key] - count($selection));
        }
        return $selection[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (!$this->get_master_foreign_key()) {
            return $this->config['type_config']['allow_multiple'] ? [] : null;
        }
        if ($this->config['type_config']['allow_multiple']) {
            return array_keys($this->load_objects());
        }
        return key($this->load_objects());
    }

    private function create_relation(string $member_key, int $score)
    {
        $member = new $this->config['type_config']['mapping_class_name']();
        $member->{$this->config['type_config']['master_fieldname']} = $this->get_master_foreign_key();
        $member->{$this->config['type_config']['member_fieldname']} = $member_key;

        foreach ($this->config['type_config']['additional_fields'] as $fieldname => $value) {
            // Determine what to do if using dot (.) in the additional fields,
            if (preg_match('/^(.+)\.(.+)$/', $fieldname, $regs)) {
                $domain = $regs[1];
                $key = $regs[2];

                // Determine what should be done with conjunction
                switch ($domain) {
                    case 'metadata':
                        $member->metadata->$key = $value;
                        break;

                    case 'parameter':
                        $member->set_parameter('midcom.helper.datamanager2.mnrelation', $key, $value);
                        break;
                }

                continue;
            }

            $member->{$fieldname} = $value;
        }
        if (!empty($this->config['type_config']['sortable'])) {
            $member->metadata->score = $score;
        }

        if (!$member->create()) {
            throw new midcom_error("Failed to create a new member record for key {$member_key}: " . midcom_connection::get_error_string());
        }
    }

    private function load_objects() : array
    {
        $qb = midcom::get()->dbfactory->new_query_builder($this->config['type_config']['mapping_class_name']);
        $qb->add_constraint($this->config['type_config']['master_fieldname'], '=', $this->get_master_foreign_key());

        if (   $this->config['type_config']['sortable']
            && preg_match('/^(ASC|DESC)/i', $this->config['type_config']['sortable_sort_order'], $regs)) {
            $order = strtoupper($regs[1]);
            $qb->add_order('metadata.score', $order);
        }

        foreach ($this->config['type_config']['constraints'] as $constraint) {
            $qb->add_constraint($this->config['type_config']['member_fieldname'] . '.' . $constraint['field'], $constraint['op'], $constraint['value']);
        }

        foreach ($this->config['type_config']['additional_fields'] as $fieldname => $value) {
            $qb->add_constraint($fieldname, '=', $value);
        }

        $indexed = [];
        $results = $qb->execute();
        foreach ($results as $result) {
            $indexed[$result->{$this->config['type_config']['member_fieldname']}] = $result;
        }
        return $indexed;
    }

    /**
     * Returns the foreign key of the master object. This is either the ID or the GUID of
     * the master object, depending on the $master_is_id member.
     */
    private function get_master_foreign_key()
    {
        if ($this->config['type_config']['master_is_id']) {
            return $this->object->id;
        }
        return $this->object->guid;
    }
}
