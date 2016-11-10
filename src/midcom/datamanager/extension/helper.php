<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Experimental helper class
 */
class helper
{
    public static function resolve_options(array $defaults, array $values)
    {
        //@todo: This line makes backward compat easier, but circumvents some validation.
        $values = array_intersect_key($values, $defaults);
        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaults);
        return $resolver->resolve($values);
    }

    public static function merge_defaults(array $defaults, array $values)
    {
        return array_merge($defaults, $values);
    }
}
