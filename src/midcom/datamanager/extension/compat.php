<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

/**
 * Typename converter
 *
 * (on PHP 5.5+, this could be replaced by use statements & CLASS constants in the caller)
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
        if (class_exists('midcom\datamanager\extension\type\\' . $shortname)) {
            return 'midcom\datamanager\extension\type\\' . $shortname;
        }
        if (class_exists('Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type')) {
            return 'Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type';
        }
        return $shortname;
    }
}
