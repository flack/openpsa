<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use midcom_baseclasses_components_configuration;
use midcom_error;

/**
 * HTMLPurifier integration
 */
class purifySubscriber implements EventSubscriberInterface
{
    private array $config;

    public function __construct(array $config)
    {
        if (!class_exists('HTMLPurifier')) {
            throw new midcom_error('HTMLPurifier is missing');
        }

        $this->config = $config ?: $this->get_from_global_config('html_purify_config');
    }

    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SUBMIT => 'purify_content'];
    }

    public function purify_content(FormEvent $event)
    {
        if (   isset($this->config['Cache']['SerializerPath'])
            && !file_exists($this->config['Cache']['SerializerPath'])) {
            mkdir($this->config['Cache']['SerializerPath']);
        }

        $purifier_config = \HTMLPurifier_Config::create($this->config);
        $name = $event->getForm()->getName();
        // Set local IDPrefix to field name...
        if (!empty($this->config['Attr']['IDPrefix'])) {
            $purifier_config->set('Attr.IDPrefixLocal', "{$name}_");
        }

        // Load custom element/attribute definitions
        $definitions = $this->get_from_global_config('html_purify_HTMLDefinition');
        if (   !empty($definitions)
            && $def = $purifier_config->maybeGetRawHTMLDefinition()) {
            if (!empty($definitions['addAttribute'])) {
                foreach (array_filter((array) $definitions['addAttribute'], 'is_array') as $attrdef) {
                    $def->addAttribute(...$attrdef);
                }
            }
            if (!empty($definitions['addElement'])) {
                foreach (array_filter((array) $definitions['addElement'], 'is_array') as $elemdef) {
                    $def->addElement(...$elemdef);
                }
            }
        }
        $purifier = new \HTMLPurifier($purifier_config);

        $data = $event->getData();
        try {
            $data = $purifier->purify($data);
        } catch (\Exception $e) {
            debug_add("HTML Purifier failed: " . $e->getMessage(), MIDCOM_LOG_WARN);
        }
        $event->setData($data);
    }

    private function get_from_global_config(string $key)
    {
        return midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config')->get($key);
    }
}
