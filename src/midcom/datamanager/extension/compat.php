<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use midcom;
use midcom\datamanager\storage\container\dbacontainer;

/**
 * Converter for data from old DM2 style schemas
 */
class compat
{
    /**
     * Provide fully qualified type names
     *
     * @param string $shortname
     * @return string
     */
    public static function get_type_name($shortname)
    {
        if (class_exists('midcom\datamanager\extension\type\\' . $shortname . 'Type')) {
            return 'midcom\datamanager\extension\type\\' . $shortname . 'Type';
        }
        if (class_exists('Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type')) {
            return 'Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type';
        }
        return $shortname;
    }

    /**
     * Convert schema config to type settings
     *
     * @param array $config
     * @param mixed $storage
     * @return array
     */
    public static function get_settings(array $config, $storage)
    {
        if ($config['write_privilege'] !== null) {
            if (   array_key_exists('group', $config['write_privilege'])
                && !midcom::get()->auth->is_group_member($config['write_privilege']['group'])) {
                $config['readonly'] = true;
            }
            if (   array_key_exists('privilege', $config['write_privilege'])
                && $storage instanceof dbacontainer
                && !$storage->get_value()->can_do($config['write_privilege']['privilege'])) {
                $config['readonly'] = true;
            }
        }

        return [
            'label' => $config['title'],
            'widget_config' => $config['widget_config'],
            'type_config' => $config['type_config'],
            'required' => $config['required'],
            'constraints' => $config['validation'],
            'dm2_type' => $config['type'],
            'dm2_storage' => $config['storage'],
            'start_fieldset' => $config['start_fieldset'],
            'end_fieldset' => $config['end_fieldset'],
            'index_method' => $config['index_method'],
            'attr' => ['readonly' => $config['readonly']],
            'helptext' => $config['helptext'],
            'storage' => $storage,
            'hidden' => $config['hidden']
        ];
    }
}
