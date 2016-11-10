<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

/**
 * Symonfy 2.7/2.8 compat class
 */
class compat
{
    public static function is_legacy()
    {
        return !method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix');
    }

    /**
     * Provide type names compatible across Symfony versions
     *
     * @param string $shortname
     * @return string
     */
    public static function get_type_name($shortname)
    {
        if (!static::is_legacy()) {
            if (class_exists('midcom\datamanager\extension\type\\' . $shortname)) {
                return 'midcom\datamanager\extension\type\\' . $shortname;
            }
            if (class_exists('Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type')) {
                return 'Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type';
            }
        }

        return $shortname;
    }
}
