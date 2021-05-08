<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use midcom\datamanager\renderer;
use midcom;

/**
 * Experimental template class
 */
abstract class base
{
    /**
     * @var renderer
     */
    protected $renderer;

    public function __construct(renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function form(FormView $view, array $data)
    {
        $string = $this->renderer->start($view, $data);
        $string .= $this->renderer->widget($view, $data);
        return $string . $this->renderer->end($view, $data);
    }

    public function form_widget(FormView $view, array $data)
    {
        if ($data['compound']) {
            return $this->renderer->block($view, 'form_widget_compound');
        }
        return $this->renderer->block($view, 'form_widget_simple');
    }

    public function form_widget_compound(FormView $view, array $data)
    {
        $string = '<div ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        if (!$view->parent && $data['errors']) {
            $string .= $this->renderer->errors($view);
        }
        $string .= $this->renderer->block($view, 'form_rows');
        $string .= $this->renderer->rest($view);
        return $string . '</div>';
    }

    public function form_rest(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            if (!$child->isRendered()) {
                $string .= $this->renderer->row($child);
            }
        }
        return $string;
    }

    public function widget_attributes(FormView $view, array $data)
    {
        $attributes = $data['attr'];
        $attributes['id'] = $data['id'];
        $attributes['name'] = $data['full_name'];
        if (!empty($data['readonly'])) {
            $attributes['readonly'] = 'readonly';
        }
        if ($data['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        if ($data['required']) {
            $attributes['required'] = 'required';
        }
        return $this->attributes($attributes, true);
    }

    public function widget_container_attributes(FormView $view, array $data)
    {
        $attr = $data['attr'];
        if (!empty($data['id'])) {
            $attr['id'] = $data['id'];
        }
        if (!$view->parent) {
            unset($attr['id']);
            $attr['class'] = 'form';
        }

        return $this->attributes($attr);
    }

    public function choice_widget(FormView $view, array $data)
    {
        if ($data['expanded']) {
            return $this->renderer->block($view, 'choice_widget_expanded');
        }
        return $this->renderer->block($view, 'choice_widget_collapsed');
    }

    public function button_attributes(FormView $view, array $data) : string
    {
        $attributes = $data['attr'];
        $attributes['id'] = $data['id'];
        $attributes['name'] = $data['full_name'];
        if ($data['disabled']) {
            $attributes['disabled'] = 'disabled';
        }

        return $this->attributes($attributes);
    }

    public function escape(string $input) : string
    {
        return htmlentities($input, ENT_COMPAT, 'utf-8');
    }

    public function attributes(array $attributes, bool $autoescape = false) : string
    {
        $rendered = [];
        foreach ($attributes as $name => $value) {
            if ($value === false) {
                continue;
            }
            if ($value === true) {
                $value = $name;
            }
            if ($autoescape) {
                $value = $this->escape($value);
            }
            $rendered[] = sprintf('%s="%s"', $name, $value);
        }
        return implode(' ', $rendered);
    }

    public function jsinit(string $code) : string
    {
        return "<script>$code</script>";
    }

    protected function add_head_elements_for_codemirror(array $modes)
    {
        $prefix = MIDCOM_STATIC_URL . '/midcom.datamanager/codemirror-5.46.0/';
        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_stylesheet($prefix . 'lib/codemirror.css');
        midcom::get()->head->add_stylesheet($prefix . 'theme/eclipse.css');
        midcom::get()->head->add_jsfile($prefix . 'lib/codemirror.js');
        foreach ($modes as $mode) {
            midcom::get()->head->add_jsfile($prefix . 'mode/' . $mode . '/' . $mode . '.js');
        }
        midcom::get()->head->add_jsfile($prefix . 'addon/edit/matchbrackets.js');
        midcom::get()->head->add_jsfile($prefix . 'addon/dialog/dialog.js');
        midcom::get()->head->add_stylesheet($prefix . 'addon/dialog/dialog.css');
        midcom::get()->head->add_jsfile($prefix . 'addon/search/searchcursor.js');
        midcom::get()->head->add_jsfile($prefix . 'addon/search/match-highlighter.js');
        midcom::get()->head->add_jsfile($prefix . 'addon/search/search.js');
    }
}
