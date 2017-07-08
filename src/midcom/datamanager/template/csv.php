<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use midcom;

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

    public function blobs_widget(FormView $view, array $data)
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
        if (empty($data['value'])) {
            return '';
        }
        if (array_key_exists('archival', $data['value'])) {
            return $data['value']['archival']['url'];
        }
        if (array_key_exists('main', $data['value'])) {
            return $data['value']['main']['url'];
        }
        $img = reset($data['value']);
        return $img['url'];
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        $options = json_decode($data['handler_options'], true);
        return implode(', ', $options['preset']);
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (!empty($data['data'])) {
            foreach ($data['choices'] as $choice) {
                if ($data['is_selected']($choice->value, (string) $data['data'])) {
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
