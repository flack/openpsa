<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

/**
 * Experimental extension class
 */
class buttonExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'operation' => '',
        ]);
    }

    // Symfony < 4.2 compat
    public function getExtendedType()
    {
        return ButtonType::class;
    }

    public static function getExtendedTypes()
    {
        return [ButtonType::class];
    }
}
