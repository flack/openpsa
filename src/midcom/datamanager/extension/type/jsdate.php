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
use midcom\datamanager\extension\transformer\jsdate as jsdatetransformer;
use midcom;
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\NotBlank;
use midcom\datamanager\extension\compat;

/**
 * Experimental jsdate type
 */
class jsdate extends AbstractType
{
    const UNIXTIME = 'UNIXTIME';

    const ISO = 'ISO';

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
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'error_bubbling' => false
        ));
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = array(
                'showOn' => 'both',
                'format' => '%Y-%m-%d %H:%M',
                'hide_seconds' => true,
                'show_time' => true,
                'maxyear' => ($options['type_config']['storage_type'] == jsdate::UNIXTIME) ? 2030 : 9999,
                'minyear' => ($options['type_config']['storage_type'] == jsdate::UNIXTIME) ? 1970 : 0,
            );
            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = array(
                'storage_type' => jsdate::ISO,
            );
            return helper::resolve_options($type_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new jsdatetransformer($options));

        $date_options = array(
            'widget' => 'single_text'
        );

        if ($options['required']) {
            $date_options['constraints'] = array(new NotBlank());
        }

        $builder->add('date', compat::get_type_name('date'), $date_options);
        if ($options['widget_config']['show_time']) {
            $pattern = '[0-2][0-9]:[0-5][0-9]';
            if (!$options['widget_config']['hide_seconds']) {
                $pattern .= ':[0-5][0-9]';
            }
            $time_options = array(
                'widget' => 'single_text',
                'with_seconds' => !$options['widget_config']['hide_seconds'],
                'attr' => array('size' => 11, 'pattern' => $pattern),
                'error_bubbling' => true,
            );
            if ($options['required']) {
                $time_options['constraints'] = array(new NotBlank());
            }

            $builder->add('time', compat::get_type_name('time'), $time_options);
        }

        $head = midcom::get()->head;
        $head->enable_jquery_ui(array('datepicker'));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $init_max = new DateTime($options['widget_config']['maxyear'] . '-12-31');
        $init_min = new DateTime($options['widget_config']['minyear'] . '-01-01');
        if (!empty($options['max_date'])) {
            $init_max = new DateTime($options['max_date']);
        }
        if (!empty($options['min_date'])) {
            $init_min = new DateTime($options['min_date']);
        }

        //need this due to js Date begins to count the months with 0 instead of 1
        $init_max_month = $init_max->format('n') - 1;
        $init_min_month = $init_min->format('n') - 1;

        $script = <<<EOT
<script type="text/javascript">
        jQuery(document).ready(
        function()
        {
            jQuery("#{$view['date']->vars['id']}").datepicker(
            {
              maxDate: new Date({$init_max->format('Y')}, {$init_max_month}, {$init_max->format('d')}),
              minDate: new Date({$init_min->format('Y')}, {$init_min_month}, {$init_min->format('d')}),
              dateFormat: 'yy-mm-dd',
              prevText: '',
              nextText: '',
              showOn: '{$options['widget_config']['showOn']}'
                    //altFormat: 'yyyy-mm-dd',
                    //altField: '#ID',
                    });
        });
</script>
EOT;
        $view->vars['jsinit'] = $script;
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
        return 'jsdate';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return compat::get_type_name('form');
    }
}
