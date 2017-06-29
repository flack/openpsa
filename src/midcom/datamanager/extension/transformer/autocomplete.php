<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Experimental autocomplete transformer
 */
class autocomplete implements DataTransformerInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform($input)
    {
        return ['selection' => $input];
    }

    public function reverseTransform($array)
    {
        if ($this->config['type_config']['allow_multiple']) {
            return $array['selection'];
        }
        if (!empty($array['selection'])) {
            return reset($array['selection']);
        }
    }
}
