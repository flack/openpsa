<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Form;
use midcom_core_dbaobject;
use midcom_core_context;
use midcom;
use midcom\datamanager\extension\transformer\multipleTransformer;
use midcom\datamanager\storage\recreateable;
use midcom\datamanager\extension\type\schemaType;
use midcom\datamanager\extension\type\toolbarType;
use midcom\datamanager\storage\container\container;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Experimental datamanager class
 */
class datamanager
{
    private schemadb $schemadb;

    private ?schema $schema = null;

    private ?container $storage = null;

    private array $defaults = [];

    private ?renderer $renderer = null;

    private ?Form $form = null;

    public function __construct(schemadb $schemadb)
    {
        $this->schemadb = $schemadb;
    }

    private static function get_factory() : FormFactoryInterface
    {
        return midcom::get()->getContainer()->get('form.factory');
    }

    public static function from_schemadb(string $path) : self
    {
        return new static(schemadb::from_path($path));
    }

    public function set_defaults(array $defaults) : self
    {
        $this->defaults = $defaults;
        return $this;
    }

    public function set_storage(midcom_core_dbaobject $storage = null, string $schemaname = null) : self
    {
        if (   $schemaname === null
            && !empty($storage->id)) {
            $schemaname = $storage->get_parameter('midcom.helper.datamanager2', 'schema_name');
        }

        $this->set_schema($schemaname);

        $defaults = array_merge($this->schema->get_defaults(), $this->defaults);
        if ($storage === null) {
            $this->storage = new storage\container\nullcontainer($this->schema, $defaults);
        } else {
            $this->storage = new storage\container\dbacontainer($this->schema, $storage, $defaults);
        }

        if ($this->form !== null) {
            if ($this->form->isSubmitted()) {
                $this->form = null;
            } else {
                $this->form->setData($this->storage);
            }
        }

        return $this;
    }

    private function set_schema(?string $name)
    {
        if ($name && !$this->schemadb->has($name)) {
            debug_add("Given schema name {$name} was not found, reverting to default.", MIDCOM_LOG_INFO);
            $name = null;
        }

        $schema = ($name) ? $this->schemadb->get($name) : $this->schemadb->get_first();
        if ($this->schema !== null && $this->schema->get_name() !== $schema->get_name()) {
            $this->form = null;
        }
        $this->schema = $schema;
    }

    public function get_schema(string $name = null) : schema
    {
        if ($name) {
            return $this->schemadb->get($name);
        }
        if ($this->schema === null) {
            $this->set_schema($name);
        }
        return $this->schema;
    }

    public function get_storage() : container
    {
        if (!$this->storage) {
            $this->set_storage(null);
        }
        return $this->storage;
    }

    public function get_renderer($template = null, bool $skip_empty = false) : renderer
    {
        if ($this->renderer === null) {
            $this->renderer = new renderer(new engine);
            $this->renderer->set_l10n($this->schema->get_l10n());
        }
        if ($template) {
            if (is_string($template)) {
                $config = \midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config');
                $templates = $config->get_array('templates');
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

    public function get_controller(string $name = null) : controller
    {
        return new controller($this, $name);
    }

    public function get_form(?string $name = null, bool $reset = false) : Form
    {
        if ($reset) {
            $this->form = null;
        }

        if (   $this->form === null
            || ($name && $this->form->getName() != $name)) {
            $this->build_form($this->get_builder($name));
        }
        return $this->form;
    }

    public function get_builder(string $name = null) : FormBuilderInterface
    {
        $config = [
            'schema' => $this->get_schema()
        ];
        $builder = self::get_factory()->createNamedBuilder($this->get_name($name), schemaType::class, null, $config);
        $builder->add('form_toolbar', toolbarType::class, [
            'operations' => $this->schema->get('operations'),
            'index_method' => 'noindex'
        ]);
        return $builder;
    }

    public function build_form(FormBuilderInterface $builder) : self
    {
        $this->form = $builder->getForm()
            ->setData($this->get_storage());

        return $this;
    }

    private function get_name(?string $name) : string
    {
        if (!$name && $name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)) {
            // Replace the dots in the component name with underscores
            $name = midcom::get()->componentloader->path_to_prefix($name);
        }
        return $name ?: 'midcom_helper_datamanager2';
    }

    public function get_content_raw() : array
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

    public function get_content_csv() : array
    {
        return $this->render('csv');
    }

    public function get_content_html() : array
    {
        return $this->render('view');
    }

    public function render(string $type) : array
    {
        $ret = [];

        $renderer = $this->get_renderer($type);
        foreach ($renderer->get_view()->children as $name => $value) {
            if ($name == 'form_toolbar') {
                continue;
            }
            $ret[$name] = $renderer->widget($value);
        }
        return $ret;
    }

    public function display_view(bool $skip_empty = false)
    {
        $renderer = $this->get_renderer('view', $skip_empty);
        echo $renderer->block($renderer->get_view(), 'form');
    }

    public function recreate() : bool
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
