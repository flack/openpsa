<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Color type
 */
class colorType extends AbstractType
{
    public function getParent()
    {
        return TextType::class;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'color';
    }
}
