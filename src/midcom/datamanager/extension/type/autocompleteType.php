<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\transformer\autocompleteTransformer;
use midcom\datamanager\extension\transformer\jsonTransformer;
use midcom\datamanager\extension\transformer\multipleTransformer;
use midcom\datamanager\extension\helper;
use midcom\datamanager\helper\autocomplete as autocomplete_helper;
use midcom;
use midcom_error;
use midcom_connection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Experimental autocomplete type
 */
class autocompleteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('error_bubbling', false);
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'creation_mode_enabled' => false,
                'class' => null,
                'component' => null,
                'id_field' => 'guid',
                'constraints' => [],
                'result_headers' => [],
                'orders' => [],
                'auto_wildcards' => 'both',
                'creation_handler' => null,
                'creation_default_key' => null,
                'titlefield' => null,
                'categorize_by_parent_label' => false,
                'searchfields' => [],
                'min_chars' => 2,
                'sortable' => false
            ];

            if (!empty($value['clever_class'])) {
                $config = \midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config');

                /** @var \midcom_helper_configuration $config */
                $config = $config->get('clever_classes');
                if (!array_key_exists($value['clever_class'], $config)) {
                    throw new midcom_error('Invalid clever class specified');
                }
                $value = array_merge($config[$value['clever_class']], $value);
            }

            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'options' => [],
                'constraints' => [],
                'allow_other' => false,
                'allow_multiple' => ($options['dm2_type'] == 'mnrelation'),
                'require_corresponding_option' => true,
                'multiple_storagemode' => 'serialized',
                'multiple_separator' => '|'
            ];

            $resolved = helper::resolve_options($type_defaults, $value);
            if (empty($resolved['constraints']) && !empty($options['widget_config']['constraints'])) {
                $resolved['constraints'] = $options['widget_config']['constraints'];
            }
            return $resolved;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new autocompleteTransformer($options));
        $builder->add('selection', HiddenType::class);
        $builder->get('selection')->addViewTransformer(new jsonTransformer);

        if ($options['type_config']['allow_multiple'] && $options['dm2_type'] == 'select') {
            $builder->get('selection')->addModelTransformer(new multipleTransformer($options));
        }

        $builder->add('search_input', SearchType::class, ['mapped' => false]);

        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/autocomplete.css');

        $components = ['menu', 'autocomplete'];
        if ($options['widget_config']['sortable']) {
            $components[] = 'mouse';
            $components[] = 'sortable';
        }
        if ($options['widget_config']['creation_mode_enabled']) {
            $components = array_merge($components, ['mouse', 'draggable', 'resizable', 'button', 'dialog']);
        }
        $head->enable_jquery_ui($components);
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/autocomplete.js');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $handler_url = midcom_connection::get_url('self') . 'midcom-exec-midcom.datamanager/autocomplete.php';

        $preset = [];
        if (!empty($view->children['selection']->vars['data'])) {
            foreach (array_filter((array) $view->children['selection']->vars['data']) as $identifier) {
                if ($options['widget_config']['id_field'] == 'id') {
                    $identifier = (int) $identifier;
                }
                try {
                    $object = call_user_func([$options['widget_config']['class'], 'get_cached'], $identifier);
                    $preset[$identifier] = autocomplete_helper::create_item_label($object, $options['widget_config']['result_headers'], $options['widget_config']['titlefield']);
                } catch (midcom_error $e) {
                    $e->log();
                }
            }
        }

        $handler_options = $options['widget_config'];
        $handler_options['constraints'] = $options['type_config']['constraints'];
        $handler_options['handler_url'] = $handler_url;
        $handler_options['allow_multiple'] = $options['type_config']['allow_multiple'];
        $handler_options['preset'] = $preset;
        $handler_options['preset_order'] = array_reverse(array_keys($preset));

        $view->vars['min_chars'] = $options['widget_config']['min_chars'];
        $view->vars['handler_options'] = json_encode($handler_options);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FormType::class;
    }
}
