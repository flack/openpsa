<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\helper;
use midcom;
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Count;

/**
 * Experimental images type
 */
class subform extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array
        (
        	'allow_add' => true,
        	'allow_delete' => true,
        	'prototype' => true,
        	'prototype_name' => '__name__',
        	'options' => array('required' => false), //@todo no idea why this is necessary
        	'delete_empty' => true,
        	'error_bubbling' => false
        ));
        $resolver->setNormalizers(array
        (
            'type' => function (Options $options, $value)
            {
                return $options['dm2_type'];
            },
            'type_config' => function (Options $options, $value)
            {
                $widget_defaults = array
                (
                    'sortable' => false,
                    'max_count' => 0
                );
                return helper::resolve_options($widget_defaults, $value);
            },
            'constraints' => function (Options $options, $value)
            {
            	$validation = array();
            	if ($options['type_config']['max_count'] > 0)
            	{
            		$validation['max'] = $options['type_config']['max_count'];
            	}
            	if ($options['required'])
            	{
            		$validation['min'] = 1;
            	}
            	if (!empty($validation))
            	{
            		return array(new Count($validation));
            	}
            	return $validation;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);

        $builder->addEventSubscriber(new ResizeFormListener($options['type'], array('widget_config' => $options['widget_config'])));

        $head = midcom::get()->head;
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/subform.js');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    	parent::buildView($view, $form, $options);
    	$view->vars['max_count'] = $options['type_config']['max_count'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'subform';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'form';
    }
}