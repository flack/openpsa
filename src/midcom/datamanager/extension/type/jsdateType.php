<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\helper;
use midcom\datamanager\extension\transformer\jsdateTransformer;
use midcom;
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\NotBlank;
use midcom\datamanager\validation\laterthan;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Experimental jsdate type
 */
class jsdateType extends AbstractType
{
    const UNIXTIME = 'UNIXTIME';

    const ISO = 'ISO';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'error_bubbling' => false
        ]);
        helper::add_normalizers($resolver, [
            'type_config' => [
                'storage_type' => self::ISO,
                'min_date' => null,
                'max_date' => null,
                'later_than' => null
            ]
        ]);
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'showOn' => 'both',
                'format' => '%Y-%m-%d %H:%M',
                'hide_seconds' => true,
                'show_time' => true,
                'maxyear' => ($options['type_config']['storage_type'] == self::UNIXTIME) ? 2030 : 9999,
                'minyear' => ($options['type_config']['storage_type'] == self::UNIXTIME) ? 1970 : 0,
            ];
            return helper::normalize($widget_defaults, $value);
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if ($options['type_config']['later_than']) {
                $value[] = new laterthan($options['type_config']['later_than']);
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new jsdateTransformer($options));

        $input_options = ['attr' => ['size' => 10]];
        $date_options = ['widget' => 'single_text'];
        if ($options['required']) {
            $input_options['attr']['required'] = true;
            $date_options['constraints'] = [new NotBlank];
        }

        $builder->add('date', DateType::class, $date_options);
        $builder->add('input', TextType::class, $input_options);

        if ($options['widget_config']['show_time']) {
            $pattern = '[0-2][0-9]:[0-5][0-9]';
            if (!$options['widget_config']['hide_seconds']) {
                $pattern .= ':[0-5][0-9]';
            }
            $time_options = [
                'widget' => 'single_text',
                'with_seconds' => !$options['widget_config']['hide_seconds'],
                'attr' => ['size' => 11, 'pattern' => $pattern],
                'error_bubbling' => true,
            ];
            if ($options['required']) {
                $time_options['attr']['required'] = true;
                $time_options['constraints'] = [new NotBlank()];
            }

            $builder->add('time', TimeType::class, $time_options);
        }

        $head = midcom::get()->head;
        $head->enable_jquery_ui(['datepicker']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $init_max = new DateTime($options['widget_config']['maxyear'] . '-12-31');
        $init_min = new DateTime($options['widget_config']['minyear'] . '-01-01');

        if (!empty($options['type_config']['max_date'])) {
            $init_max = new DateTime($options['type_config']['max_date']);
        }
        if (!empty($options['type_config']['min_date'])) {
            $init_min = new DateTime($options['type_config']['min_date']);
        }

        //need this due to js Date begins to count the months with 0 instead of 1
        $init_max_month = $init_max->format('n') - 1;
        $init_min_month = $init_min->format('n') - 1;

        $script = <<<EOT
<script type="text/javascript">
    $(document).ready(function() {
        $("#{$view['input']->vars['id']}").datepicker({
            maxDate: new Date({$init_max->format('Y')}, {$init_max_month}, {$init_max->format('d')}),
            minDate: new Date({$init_min->format('Y')}, {$init_min_month}, {$init_min->format('d')}),
            dateFormat: $.datepicker.regional[Object.keys($.datepicker.regional)[Object.keys($.datepicker.regional).length - 1]].dateFormat || $.datepicker.ISO_8601,
            altField: "#{$view['date']->vars['id']}",
            altFormat: $.datepicker.ISO_8601,
            prevText: '',
            nextText: '',
            showOn: '{$options['widget_config']['showOn']}',
            buttonText: '&#xf073;'
        }).on('change', function() {
            if ($(this).val() == '') {
                $("#{$view['date']->vars['id']}").val('');
            }
        });
        if ($("#{$view['date']->vars['id']}").val() && $("#{$view['date']->vars['id']}").val() !== '0000-00-00') {
            $("#{$view['input']->vars['id']}").datepicker('setDate', new Date($("#{$view['date']->vars['id']}").val()));
        }
    });
</script>
EOT;
        $view->vars['jsinit'] = $script;
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
        return FormType::class;
    }
}
