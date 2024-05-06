<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Experimental autocomplete transformer
 */
class autocompleteTransformer implements DataTransformerInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform(mixed $input) : mixed
    {
        return ['selection' => $input];
    }

    public function reverseTransform(mixed $array) : mixed
    {
        if ($this->config['type_config']['allow_multiple']) {
            return $array['selection'];
        }
        if (!empty($array['selection'])) {
            return reset($array['selection']);
        }
        return null;
    }
}
