<?php

use midcom\datamanager\template\base;
use Symfony\Component\Form\FormView;

class datamanager_form extends base
{
    public function form_start(FormView $view, array $data)
    {
        return '["' . $data['name'] . '" => [';
    }

    public function form_end(FormView $view, array $data)
    {
        return ']]';
    }

    public function form_errors(FormView $view, array $data)
    {
    }

    public function form_widget_compound(FormView $view, array $data)
    {
        $string = $this->renderer->block($view, 'form_rows');
        $string .= $this->renderer->rest($view);
        return $string;
    }

    public function form_rows(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            $string .= $this->renderer->row($child);
        }
        return $string;
    }

    public function form_row(FormView $view, array $data)
    {
        return '"' . $data['name'] . '" => ' . $this->renderer->widget($view) . ',';
    }

    public function button_row(FormView $view, array $data)
    {
    }

    public function toolbar_row(FormView $view, array $data)
    {
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        return $this->form_widget_simple($view, $data);
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        return '"' . $data['value'] . '"';
    }

    public function photo_widget(FormView $view, array $data)
    {
        return '[]';
    }

    public function radiocheckselect_widget(FormView $view, array $data)
    {
        foreach ($view->children as $child) {
            if ($child->vars['checked']) {
                return '"' . $child->vars['value']. '"';
            }
        }
        return '';
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        return '["selection" => ' . $this->renderer->widget($view['selection']) . ']';
    }


    public function jsdate_widget(FormView $view, array $data)
    {
        $string = '["date" => ' . $this->renderer->widget($view['date']) . ',';

        if (isset($view['time'])) {
            $string .= '"time" => ' . $this->renderer->widget($view['time']);
        }
        return $string . ']';
    }
}
