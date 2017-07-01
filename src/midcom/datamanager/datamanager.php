<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use midcom\datamanager\extension\schemaextension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Form;
use midcom_core_dbaobject;
use midcom_core_context;
use midcom;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use midcom\datamanager\extension\compat;
use midcom\datamanager\extension\transformer\multiple;

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
        if (static::$factory === null) {
            $fb = new FormFactoryBuilder();
            $fb->addExtension(new schemaextension());
            $fb->addExtension(new CoreExtension());

            $vb = Validation::createValidatorBuilder();
            $lang = midcom::get()->i18n->get_current_language();
            $translator = new Translator($lang);
            $translator->addLoader('xlf', new XliffFileLoader);
            $rc = new \ReflectionClass($vb);
            $path = dirname($rc->getFileName());
            $translator->addResource('xlf', $path . '/Resources/translations/validators.' . $lang . '.xlf', $lang);
            $vb->setTranslator($translator);
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
    public function set_storage(midcom_core_dbaobject $storage = null, $schema = null)
    {
        if (   $schema === null
            && !empty($storage->id)) {
            $schema = $storage->get_parameter('midcom.helper.datamanager2', 'schema_name');
        }
        $this->schema = ($schema) ? $this->schemadb->get($schema) : $this->schemadb->get_first();

        $defaults = array_merge($this->schema->get_defaults(), $this->defaults);

        if ($storage === null) {
            $this->storage = new storage\container\nullcontainer($this->schema, $defaults);
        } else {
            $this->storage = new storage\container\dbacontainer($this->schema, $storage, $defaults);
        }
        $this->form = null;

        return $this;
    }

    /**
     *
     * @return \midcom\datamanager\schema
     */
    public function get_schema()
    {
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
    public function get_renderer()
    {
        if ($this->renderer === null) {
            $this->renderer = new renderer(new engine);
            $this->renderer->set_l10n($this->schema->get_l10n());
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
     * @return Form
     */
    public function get_form($name = null)
    {
        if ($name == null) {
            $name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
            // Replace the dots in the component name with underscores
            $name = midcom::get()->componentloader->path_to_prefix($name);
        }
        if (! $name) {
            // Fallback for componentless operation
            $name = 'midcom_helper_datamanager2';
        }

        if (   $this->form === null
            || $this->form->getName() != $name) {
            $builder = self::get_factory()
                ->createNamedBuilder($name, compat::get_type_name('form'), $this->get_storage());
            $this->form = $this->schema->build_form($builder, $this->storage);
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
                $transformer = new multiple($config);
                $ret[$field] = $transformer->transform($ret[$field]);
            }
        }

        return $ret;
    }

    public function get_content_html()
    {
        $ret = [];

        $view = $this->get_form()->createView();
        $renderer = $this->get_renderer();
        $renderer->set_template($view, new template\view($renderer));

        foreach ($view->children as $name => $value) {
            if ($name == 'form_toolbar') {
                continue;
            }
            $ret[$name] = $renderer->widget($value);
        }
        return $ret;
    }

    public function display_view($skip_empty = false)
    {
        $view = $this->get_form()->createView();
        $renderer = $this->get_renderer();
        $renderer->set_template($view, new template\view($renderer, $skip_empty));
        echo $renderer->block($view, 'form');
    }
}
