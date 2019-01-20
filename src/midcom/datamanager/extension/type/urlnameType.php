<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\extension\helper;
use midcom\datamanager\validation\urlname as validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Experimental urlname type
 */
class urlnameType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('write_privilege', ['privilege' => 'midcom:urlname']);

        helper::add_normalizers($resolver, [
            'type_config' => [
                'allow_catenate' => false,
                'allow_unclean' => false,
                'title_field' => 'title',
                'purify' => false,
                'purify_config' => []
            ]
        ]);

        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            $validator_options = [
                'allow_catenate' => $options['type_config']['allow_catenate'],
                'allow_unclean' => $options['type_config']['allow_unclean'],
                'title_field' => $options['type_config']['title_field'],
                'property' => $options['storage'],
            ];
            $value[] = new validator($validator_options);
            return $value;
        });
    }

    public function getParent()
    {
        return TextType::class;
    }
}
