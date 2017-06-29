<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Experimental json transformer
 */
class json implements DataTransformerInterface
{
    public function reverseTransform($input)
    {
        return (array) json_decode($input);
    }

    public function transform($array)
    {
        return json_encode((array) $array);
    }
}
