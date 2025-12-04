<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as sf_type;

/**
 * Password type
 */
class passwordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'type' => sf_type::class,
            'invalid_message' => 'passwords do not match',
            'first_options' => ['label' => 'password', 'always_empty' => false, 'attr' => ['autocomplete' => 'new-password']],
            'second_options' => ['label' => '(confirm)', 'always_empty' => false, 'attr' => ['autocomplete' => 'new-password']],
        ]);

        $resolver->setNormalizer('required', function(Options $options, $value) {
            return $value || !empty($options['widget_config']['require_password']);
        });
        $resolver->setNormalizer('first_options', function(Options $options, $value) {
            $value['label'] = $options['label'];
            return $value;
        });
    }

    public function getParent() : string
    {
        return RepeatedType::class;
    }
}