<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\validation\pattern as validator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\subscriber\purifySubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Text extension
 */
class textExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefault('constraints', []);
        helper::add_normalizers($resolver, [
            'type_config' => [
                'forbidden_patterns' => [],
                'maxlength' => 0,
                'purify' => false,
                'purify_config' => []
            ]
        ]);
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if (!empty($options['type_config']['forbidden_patterns'])) {
                $value[] = new validator(['forbidden_patterns' => $options['type_config']['forbidden_patterns']]);
            }
            if (!empty($options['type_config']['maxlength'])) {
                $value[] = new Length(max: $options['type_config']['maxlength']);
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        if (!empty($options['type_config']['purify'])) {
            $builder->addEventSubscriber(new purifySubscriber($options['type_config']['purify_config']));
        }
    }

    public static function getExtendedTypes() : iterable
    {
        return [TextType::class];
    }
}
