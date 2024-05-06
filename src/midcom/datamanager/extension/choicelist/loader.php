<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\choicelist;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;

/**
 * Loader / converter from type_config
 */
class loader implements ChoiceLoaderInterface
{
    private array $config;

    private ?ArrayChoiceList $choice_list = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList(?callable $value = null) : ChoiceListInterface
    {
        if ($this->choice_list === null) {
            $options = [];
            if (!empty($this->config['options'])) {
                $options = $this->config['options'];
            } elseif (isset($this->config['option_callback'])) {
                $classname = $this->config['option_callback'];
                $callback = new $classname($this->config['option_callback_arg']);
                $options = $callback->list_all();
            }

            $converted = [];
            foreach ($options as $key => $label) {
                // symfony expects only strings
                $converted[(string)$label] = (string)$key;
            }

            $this->choice_list = new ArrayChoiceList($converted, $value);
        }

        return $this->choice_list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        if (empty($values)) {
            return [];
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        if (empty($choices)) {
            return [];
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }
}