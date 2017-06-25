<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Experimental jsdate transformer
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
        if (   $input === false
            || $input === null) {
            return ['selection' => []];
        }

        if (   $this->config['dm2_type'] == 'select'
            && $this->config['type_config']['allow_multiple']) {
            switch ($this->config['type_config']['multiple_storagemode']) {
                case 'serialized':
                    $input = unserialize($input);
                    break;
                case 'array':
                    break;
                case 'imploded':
                    break;
                case 'imploded_wrapped':
                default:
                    throw new TransformationFailedException('Invalid storage mode ' . $this->config['type_config']['multiple_storagemode']);
            }
        }
        return ['selection' => (array) $input];
    }

    public function reverseTransform($array)
    {
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (empty($array['selection'])) {
            return;
        }

        if (count($array['selection']) == 1) {
            return reset($array['selection']);
        }

        if ($this->config['dm2_type'] !== 'select') {
            return $array['selection'];
        }

        if ($this->config['type_config']['allow_multiple']) {
            switch ($this->config['type_config']['multiple_storagemode']) {
                case 'serialized':
                    $selection = serialize($array['selection']);
                    break;
                case 'array':
                    break;
                case 'imploded':
                    break;
                case 'imploded_wrapped':
                default:
                    throw new TransformationFailedException('Invalid storage mode ' . $this->config['type_config']['multiple_storagemode']);
            }
        }

        return $selection;
    }
}
