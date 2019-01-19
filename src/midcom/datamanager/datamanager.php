<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use midcom\datamanager\extension\extension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Form;
use midcom_core_dbaobject;
use midcom_core_context;
use midcom;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use midcom\datamanager\extension\transformer\multipleTransformer;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use midcom\datamanager\storage\recreateable;
use midcom\datamanager\extension\type\schemaType;
use midcom\datamanager\extension\type\toolbarType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

/**
 * Experimental datamanager class
 */
class datamanager
{
    private $schemadb;

    /**
     *
     * @var schema
     */
    private $schema;

    /**
     *
     * @var storage\container\container
     */
    private $storage;

    /**
     *
     * @var array
     */
    private $defaults = [];

    /**
     *
     * @var renderer
     */
    private $renderer;

    /**
     *
     * @var FormFactoryInterface
     */
    private static $factory;

    /**
     *
     * @var Form
     */
    private $form;

    public function __construct(schemadb $schemadb)
    {
        $this->schemadb = $schemadb;
    }

    /**
     *
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    private static function get_factory()
    {
        if (self::$factory === null) {
            $fb = new FormFactoryBuilder();

            $lang = midcom::get()->i18n->get_current_language();
            $translator = new Translator($lang);
            $translator->addLoader('xlf', new XliffFileLoader);

            $vb = Validation::createValidatorBuilder();
            $rc = new \ReflectionClass($vb);
            $path = dirname($rc->getFileName());
            $translator->addResource('xlf', $path . '/Resources/translations/validators.' . $lang . '.xlf', $lang);
            $rc = new \ReflectionClass($fb);
            $path = dirname($rc->getFileName());
            $translator->addResource('xlf', $path . '/Resources/translations/validators.' . $lang . '.xlf', $lang);

            $vb->setTranslator($translator);

            $fb->addExtension(new extension())
                ->addExtension(new CoreExtension())
                ->addExtension(new HttpFoundationExtension())
                ->addExtension(new CsrfExtension(new CsrfTokenManager, $translator));

            $fb->addExtension(new ValidatorExtension($vb->getValidator()));

            self::$factory = $fb->getFormFactory();
        }
        return self::$factory;
    }

    public static function from_schemadb($path)
    {
        return new static(schemadb::from_path($path));
    }

    /**
     *
     * @param array $defaults
     * @return \midcom\datamanager\datamanager
     */
    public function set_defaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     *
     * @param midcom_core_dbaobject $storage
     * @param string $schema
     * @return \midcom\datamanager\datamanager
     */
    public function set_storage(midcom_core_dbaobject $storage = null, $schemaname = null)
    {
        if (   $schemaname === null
            && !empty($storage->id)) {
            $schemaname = $storage->get_parameter('midcom.helper.datamanager2', 'schema_name');
        }

        if ($schemaname && !$this->schemadb->has($schemaname)) {
            debug_add("Given schema name {$schemaname} was not found, reverting to default.", MIDCOM_LOG_INFO);
            $schemaname = null;
        }

        $schema = ($schemaname) ? $this->schemadb->get($schemaname) : $this->schemadb->get_first();
        if ($this->schema !== null && $this->schema->get_name() !== $schema->get_name()) {
            $this->form = null;
        }
        $this->schema = $schema;

        $defaults = array_merge($this->schema->get_defaults(), $this->defaults);

        if ($storage === null) {
            $this->storage = new storage\container\nullcontainer($this->schema, $defaults);
        } else {
            $this->storage = new storage\container\dbacontainer($this->schema, $storage, $defaults);
        }
        if ($this->form !== null && !$this->form->isSubmitted()) {
            $this->form->setData($this->storage);
        } else {
            $this->form = null;
        }

        return $this;
    }

    /**
     * @param string $name
     * @return \midcom\datamanager\schema
     */
    public function get_schema($name = null)
    {
        if ($name) {
            return $this->schemadb->get($name);
        }
        return $this->schema;
    }

    /**
     *
     * @return storage\container\container
     */
    public function get_storage()
    {
        if (!$this->storage) {
            $this->set_storage(null);
        }
        return $this->storage;
    }

    /**
     *
     * @return renderer
     */
    public function get_renderer($template = null, $skip_empty = false)
    {
        if ($this->renderer === null) {
            $this->renderer = new renderer(new engine);
            $this->renderer->set_l10n($this->schema->get_l10n());
        }
        if ($template) {
            if (is_string($template)) {
                $config = \midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config');
                $templates = $config->get('templates');
                if (!array_key_exists($template, $templates)) {
                    throw new \midcom_error('Template ' . $template . ' not found in config');
                }
                $template = new $templates[$template]($this->renderer, $skip_empty);
            }
            $view = $this->get_form()->createView();
            $this->renderer->set_template($view, $template);
        }
        return $this->renderer;
    }

    /**
     *
     * @param string $name
     * @return controller
     */
    public function get_controller($name = null)
    {
        return new controller($this, $name);
    }

    /**
     *
     * @param string $name
     * @param boolean $reset
     * @return Form
     */
    public function get_form($name = null, $reset = false)
    {
        if ($reset) {
            $this->form = null;
        }
        if ($name == null) {
            $name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
            // Replace the dots in the component name with underscores
            $name = midcom::get()->componentloader->path_to_prefix($name);
        }
        if (!$name) {
            // Fallback for componentless operation
            $name = 'midcom_helper_datamanager2';
        }

        if (   $this->form === null
            || $this->form->getName() != $name) {
            $this->get_storage();

            $config = [
                'schema' => $this->schema
            ];
            $builder = self::get_factory()->createNamedBuilder($name, schemaType::class, $this->storage, $config);

            $config = [
                'operations' => $this->schema->get('operations'),
                'index_method' => 'noindex'
            ];
            $builder->add('form_toolbar', toolbarType::class, $config);

            $this->form = $builder->getForm();
        }
        return $this->form;
    }

    public function get_content_raw()
    {
        $ret = [];

        foreach ($this->storage as $field => $value)
        {
            $ret[$field] = $value->get_value();
            $config = $this->schema->get_field($field);
            if (!empty($config['type_config']['allow_multiple'])) {
                $transformer = new multipleTransformer($config);
                $ret[$field] = $transformer->transform($ret[$field]);
            }
        }

        return $ret;
    }

    public function get_content_csv()
    {
        $ret = [];

        $renderer = $this->get_renderer('csv');
        foreach ($renderer->get_view()->children as $name => $value) {
            if ($name == 'form_toolbar') {
                continue;
            }
            $ret[$name] = $renderer->widget($value);
        }

        return $ret;
    }

    public function get_content_html()
    {
        $ret = [];

        $renderer = $this->get_renderer('view');
        foreach ($renderer->get_view()->children as $name => $value) {
            if ($name == 'form_toolbar') {
                continue;
            }
            $ret[$name] = $renderer->widget($value);
        }
        return $ret;
    }

    public function display_view($skip_empty = false)
    {
        $renderer = $this->get_renderer('view', $skip_empty);
        echo $renderer->block($renderer->get_view(), 'form');
    }

    public function recreate()
    {
        $ret = true;
        foreach ($this->storage as $field) {
            if (   $field instanceof recreateable
                && !$field->recreate()) {
                $ret = false;
            }
        }
        return $ret;
    }
}
