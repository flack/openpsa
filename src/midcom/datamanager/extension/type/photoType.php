<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom\datamanager\extension\helper;
use Symfony\Component\Form\AbstractType;

/**
 * Experimental photo type
 */
class photoType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        helper::add_normalizers($resolver, [
            'widget_config' => [
                'map_action_elements' => false,
                'show_title' => false,
                'show_description' => false,
                'sortable' => false
            ],
            'type_config' => [
                'do_not_save_archival' => false,
                'derived_images' => [],
                'filter_chain' => null
            ]
        ]);
    }

    public function getParent()
    {
        return imageType::class;
    }
}
