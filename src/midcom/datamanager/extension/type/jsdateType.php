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
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\NotBlank;
use midcom\datamanager\validation\laterthan;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use midcom\datamanager\validation\laterthanorequal;

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
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'error_bubbling' => false
        ]);
        helper::add_normalizers($resolver, [
            'type_config' => [
                'storage_type' => self::ISO,
                'min_date' => null,
                'max_date' => null,
                'later_than' => null,
                'later_than_or_equal' => false
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
                if ($options['type_config']['later_than_or_equal']) {
                    $value[] = new laterthanorequal($options['type_config']['later_than']);
                } else {
                    $value[] = new laterthan($options['type_config']['later_than']);
                }
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder->addModelTransformer(new jsdateTransformer($options));

        $input_options = ['attr' => [
            'size' => 10,
            'autocomplete' => 'off'
        ]];
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
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options) : void
    {
        $init_max = new DateTime($options['widget_config']['maxyear'] . '-12-31');
        $init_min = new DateTime($options['widget_config']['minyear'] . '-01-01');

        if (!empty($options['type_config']['max_date'])) {
            $init_max = new DateTime($options['type_config']['max_date']);
        }
        if (!empty($options['type_config']['min_date'])) {
            $init_min = new DateTime($options['type_config']['min_date']);
        }

        $js_options = [
            'id' => '#' . $view['input']->vars['id'],
            'alt_id' => '#' . $view['date']->vars['id'],
            'max_date' => $init_max->format('Y-m-d'),
            'min_date' => $init_min->format('Y-m-d'),
            'showOn' => $options['widget_config']['showOn']
        ];

        if (!empty($options['type_config']['later_than'])) {
            $js_options['later_than'] = '#' . $view->parent[$options['type_config']['later_than']]['input']->vars['id'];
        }

        $view->vars['jsinit'] = '<script type="text/javascript">init_datepicker(' . json_encode($js_options) . ');</script>';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() : string
    {
        return 'jsdate';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent() : ?string
    {
        return FormType::class;
    }
}
