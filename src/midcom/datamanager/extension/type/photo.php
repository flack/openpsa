<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\helper;
use midcom;
use midcom\datamanager\extension\transformer\photo as transformer;
use midcom\datamanager\validation\photo as constraint;
use midcom\datamanager\extension\compat;

/**
 * Experimental photo type
 */
class photo extends AbstractType
{
    /**
     *  Symfony 2.6 compat
     *
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'error_bubbling' => false
        ));
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = array(
                'map_action_elements' => false,
                'show_title' => false
            );
            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if ($options['required']) {
                return array(new constraint());
            }
            return array();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new transformer($options));
        $builder->add('file', compat::get_type_name('file'), array('required' => false));
        if ($options['widget_config']['show_title']) {
            $builder->add('title', compat::get_type_name('text'));
        }
        $builder->add('delete', compat::get_type_name('checkbox'), array('attr' => array(
            "class" => "midcom_datamanager_photo_checkbox"
        ), "required" => false ));
        $builder->add('identifier', compat::get_type_name('hidden'), array('data' => 'file'));

        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/photo.css');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/photo.js');
    }

    /**
     * {@inheritdoc}
     *
     * Symfony < 2.8 compat
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'photo';
    }
}
