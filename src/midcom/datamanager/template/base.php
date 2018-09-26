<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use midcom\datamanager\renderer;

/**
 * Experimental template class
 */
abstract class base
{
    /**
     *
     * @var \midcom\datamanager\renderer
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
        $string = '<div' . $this->renderer->block($view, 'widget_container_attributes') . '>';
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
        $attributes = $view->vars['attr'];
        $attributes['id'] = $data['id'];
        $attributes['name'] = $data['full_name'];
        if (!empty($attributes['readonly'])) {
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

    public function button_attributes(FormView $view, array $data)
    {
        $attributes = $data['attr'];
        $attributes['id'] = $data['id'];
        $attributes['name'] = $data['full_name'];
        if ($data['disabled']) {
            $attributes['disabled'] = 'disabled';
        }

        return $this->attributes($attributes);
    }

    public function escape($input)
    {
        return htmlentities($input, ENT_COMPAT, 'utf-8');
    }

    public function attributes(array $attributes, $autoescape = false)
    {
        $string = '';
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
            $string .= sprintf(' %s="%s"', $name, $value);
        }
        return $string;
    }

    public function jsinit($code)
    {
        $string = '<script type="text/javascript">';
        $string .= '$(document).ready(function(){';
        $string .= $code . '});';
        return $string . '</script>';
    }
}
