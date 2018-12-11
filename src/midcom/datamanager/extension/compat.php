<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use midcom;
use midcom\datamanager\storage\container\dbacontainer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use midcom_error;

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

        $settings = $config;
        $settings['label'] = $config['title'];
        $settings['dm2_type'] = $config['type'];
        $settings['dm2_storage'] = $config['storage'];
        $settings['attr']['readonly'] = $config['readonly'];
        $settings['constraints'] = self::build_constraints($config);
        $settings['storage'] = $storage;

        unset($settings['readonly']);
        unset($settings['type']);
        unset($settings['customdata']);
        unset($settings['default']);
        unset($settings['description']);
        unset($settings['title']);
        unset($settings['validation']);
        unset($settings['widget']);
        unset($settings['write_privilege']);

        return $settings;
    }

    private static function build_constraints($config)
    {
        $constraints = [];

        foreach ((array) $config['validation'] as $rule) {
            if (is_object($rule)) {
                $constraints[] = $rule;
                continue;
            }
            if ($rule['type'] === 'email') {
                $constraints[] = new Email();
            } elseif ($rule['type'] === 'regex') {
                $r_options = ['pattern' => $rule['format']];
                if (!empty($rule['message'])) {
                    $r_options['message'] = $rule['message'];
                }
                $constraints[] = new Regex($r_options);
            } else {
                throw new midcom_error($rule['type'] . ' validation not implemented yet');
            }
        }
        if ($config['required']) {
            array_unshift($constraints, new NotBlank());
        }

        return $constraints;
    }
}
