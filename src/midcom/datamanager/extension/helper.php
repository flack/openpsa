<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Experimental helper class
 */
class helper
{
    public static function add_normalizers(OptionsResolver $resolver, array $defaults)
    {
        foreach ($defaults as $key => $values) {
            $resolver->setNormalizer($key, function (Options $options, $value) use ($values) {
                return self::normalize($values, $value);
            });
        }
    }

    public static function normalize(array $defaults, array $values)
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
