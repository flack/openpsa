<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;

class view extends base
{
    public function form_start(FormView $view, array $data)
    {
        return "<div class=\"midcom_helper_datamanager2_view\">";
    }

    public function form_end(FormView $view, array $data)
    {
        return "</div>";
    }

    public function form_errors(FormView $view, array $data)
    {
        return '';
    }

    public function form_rows(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            if (    array_key_exists('start_fieldset', $child->vars)
                && $child->vars['start_fieldset'] !== null) {
                $string .= '<div class="fieldset">';
                if (!empty($child->vars['start_fieldset']['title'])) {
                    $string .= '<h2>' . $child->vars['start_fieldset']['title'] . '</h2>';
                }
            }
            $string .= $this->renderer->row($child);
            if (    array_key_exists('end_fieldset', $child->vars)
                && $child->vars['end_fieldset'] !== null) {
                $end_fieldsets = max(1, (int) $child->vars['end_fieldset']);
                for ($i = 0; $i < $end_fieldsets; $i++) {
                    $string .= '</div>';
                }
            }
        }
        return $string;
    }

    public function hidden_row(FormView $view, array $data)
    {
        return '';
    }

    public function button_row(FormView $view, array $data)
    {
        return '';
    }

    public function toolbar_row(FormView $view, array $data)
    {
        return '';
    }

    public function form_row(FormView $view, array $data)
    {
        $class = 'field field_' . $view->vars['block_prefixes'][count($view->vars['block_prefixes']) - 2];

        $string = '<div class="' . $class . '">';
        $string .= $this->renderer->label($view);
        $string .= '<div class="value">';
        $string .= $this->renderer->humanize($this->renderer->widget($view));
        return $string . '</div></div>';
    }

    public function form_label(FormView $view, array $data)
    {
        if ($data['label'] === false) {
            return '';
        }
        $label_attr = $data['label_attr'];
        $label_attr['class'] = trim('title ' . (isset($label_attr['class']) ? $label_attr['class'] : ''));
        if (!$data['label']) {
            $data['label'] = $this->renderer->humanize($data['name']);
        }
        return '<div' . $this->attributes($label_attr) . '>' . $this->renderer->humanize($data['label']) . '</div>';
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            return $this->escape($data['value']);
        }
        return '';
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        $options = json_decode($data['handler_options'], true);
        return implode(', ', $options['preset']);
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            foreach ($data['choices'] as $choice) {
                if ($choice->value === $data['value']) {
                    return $this->renderer->humanize($choice->label);
                }
            }
        }
        return '';
    }

    public function codemirror_widget(FormView $view, array $data)
    {
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes') . '>';
        $string .= $data['value'] . '</textarea>';
        if (!empty($data['codemirror_snippet'])) {
            $snippet = str_replace('{$id}', $data['id'], $data['codemirror_snippet']);
            $snippet = str_replace('{$read_only}', 'true', $snippet);
            $string .= $this->jsinit($snippet);
        }
        return $string;
    }

    public function tinymce_widget(FormView $view, array $data)
    {
        return $data['value'];
    }

    public function jsdate_widget(FormView $view, array $data)
    {
        $string = $this->renderer->widget($view['date']);

        if (isset($view['time'])) {
            $string .= ' '. $this->renderer->widget($view['time']);
        }
        return $string;
    }
}
