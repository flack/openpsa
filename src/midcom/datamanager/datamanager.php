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
use midcom_helper_misc;
use midcom;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use midcom\datamanager\extension\compat;

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
        $data = midcom_helper_misc::get_snippet_content($path);
        $data = midcom_helper_misc::parse_config($data);
        $schemadb = new schemadb;
        foreach ($data as $name => $config) {
            $schemadb->add($name, new schema($config));
        }
        return new static($schemadb);
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

        if ($storage === null) {
            $this->storage = new storage\container\nullcontainer($this->schema, $this->defaults);
        } else {
            $this->storage = new storage\container\dbacontainer($this->schema, $storage, $this->defaults);
        }
        $this->form = null;

        return $this;
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
        return new controller($this->get_form($name), $this->get_storage(), $this->get_renderer());
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
            $this->get_storage();
            $builder = self::get_factory()->createNamedBuilder($name, compat::get_type_name('form'), $this->storage);
            $this->form = $this->schema->build_form($builder);
        }
        return $this->form;
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
}
