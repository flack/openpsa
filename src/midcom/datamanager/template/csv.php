<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;

class csv extends base
{
    public function form_rows(FormView $view, array $data)
    {
        $ret = [];
        foreach ($view as $child) {
            $ret[] = $this->renderer->widget($child);
        }
        return implode(', ', array_filter($ret));
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            return $data['value'];
        }
        return '';
    }

    public function form_widget_compound(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_rows');
    }

    public function attachment_widget(FormView $view, array $data)
    {
        if (!empty($data['value']['url'])) {
            return $data['value']['url'];
        }
        return '';
    }

    public function radiocheckselect_widget(FormView $view, array $data)
    {
        $ret = [];
        foreach ($view->children as $child) {
            if ($child->vars['checked']) {
                $ret[] = $this->renderer->humanize($child->vars['label']);
            }
        }
        return implode(', ', $ret);
    }

    public function image_widget(FormView $view, array $data)
    {
        if (empty($data['value']['objects'])) {
            return '';
        }
        if (array_key_exists('archival', $data['value']['objects'])) {
            return $data['value']['objects']['archival']['url'];
        }
        if (array_key_exists('main', $data['value']['objects'])) {
            return $data['value']['objects']['main']['url'];
        }
        $img = reset($data['value']['objects']);
        return $img['url'];
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        return implode(', ', $data['handler_options']['preset']);
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (isset($data['data'])) {
            if (!empty($data['multiple'])) {
                $selection = $data['data'];
            } else {
                $selection = (string) $data['data'];
            }
            foreach ($data['choices'] as $choice) {
                if ($choice instanceof ChoiceGroupView) {
                    foreach ($choice->choices as $option) {
                        if ($data['is_selected']($option->value, $selection)) {
                            return $this->renderer->humanize($option->label);
                        }
                    }
                } elseif ($data['is_selected']($choice->value, $selection)) {
                    return $this->renderer->humanize($choice->label);
                }
            }
        }
        return '';
    }

    public function checkbox_widget(FormView $view, $data)
    {
        return ($data['checked']) ? '1' : '0';
    }

    public function tinymce_widget(FormView $view, array $data)
    {
        return $data['value'];
    }

    public function jsdate_widget(FormView $view, array $data)
    {
        if (empty($data['value']['date'])) {
            return '';
        }

        $format = $this->renderer->humanize('short date csv');

        if (isset($view['time'])) {
            $format .= (isset($data['value']['seconds'])) ? ' H:i' : ' H:i:s';
        }
        return $data['value']['date']->format($format);
    }
}
