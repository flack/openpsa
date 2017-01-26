<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        return json_encode($array);
    }
}
