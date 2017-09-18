<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * Experimental password type
 */
class password extends RepeatedType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'type' => 'Symfony\Component\Form\Extension\Core\Type\PasswordType',
            'invalid_message' => 'passwords do not match',
            'first_options' => ['label' => 'password', 'always_empty' => false],
            'second_options' => ['label' => '(confirm)', 'always_empty' => false],
        ]);

        $resolver->setNormalizer('required', function(Options $options, $value) {
            return !empty($options['widget_config']['require_password']);
        });
        $resolver->setNormalizer('first_options', function(Options $options, $value) {
            $value['label'] = $options['label'];
            return $value;
        });
    }
}