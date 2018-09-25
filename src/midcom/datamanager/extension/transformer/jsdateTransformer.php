<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use midcom\datamanager\extension\type\jsdateType;
use DateTime;

/**
 * Experimental jsdate transformer
 */
class jsdateTransformer implements DataTransformerInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform($input)
    {
        $result = ['date' => null];
        if ($this->config['widget_config']['show_time']) {
            $result['hours'] = null;
            $result['minutes'] = null;
            if (!$this->config['widget_config']['hide_seconds']) {
                $result['seconds'] = null;
            }
        }

        if (   empty($input)
            || (   $input instanceof DateTime
                && $input->format('Y-m-d H:i:s') == '0001-01-01 00:00:00')) {
            return $result;
        }

        $date = new \DateTime;
        if ($this->config['type_config']['storage_type'] === jsdateType::UNIXTIME) {
            $date->setTimestamp((int) $input);
        } elseif ($this->config['type_config']['storage_type'] === jsdateType::ISO) {
            $date->modify($input);
        }
        $result['date'] = $date;
        if ($this->config['widget_config']['show_time']) {
            $result['time'] = $date;
        }

        return $result;
    }

    public function reverseTransform($array)
    {
        if (!is_array($array)) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (   empty($array['date'])
            || (   $array['date'] instanceof DateTime
                && $array['date']->format('Y-m-d H:i:s') == '0001-01-01 00:00:00')) {
            return;
        }
        if (!empty($array['time'])) {
            $array['date']->setTime((int) $array['time']->format('G'), (int) $array['time']->format('i'), (int) $array['time']->format('s'));
        }

        if ($this->config['type_config']['storage_type'] === jsdateType::UNIXTIME) {
            return $array['date']->format('U');
        } elseif ($this->config['type_config']['storage_type'] === jsdateType::ISO) {
            return $array['date']->format('Y-m-d H:i:s');
        }
    }
}
