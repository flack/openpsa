<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;

class form extends base
{
    private function get_view_renderer()
    {
        return new view($this->renderer);
    }

    public function form_start(FormView $view, array $data)
    {
        $attributes = $data['attr'];
        $attributes['name'] = $data['name'];
        $attributes['id'] = $data['name'];
        $attributes['method'] = strtolower($data['method']);
        $attributes['action'] = $data['action'];
        $attributes['class'] = 'datamanager2';

        if ($data['multipart']) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        return '<form' . $this->attributes($attributes) . '>';
    }

    public function form_errors(FormView $view, array $data)
    {
        $string = '';
        foreach ($data['errors'] as $error) {
            $params = $error->getMessageParameters();
            $message = $error->getMessage();
            foreach ($params as $param) {
                if (strpos($message, $param)) {
                    $message = str_replace($param, $this->renderer->humanize($param), $message);
                }
            }
            $string .= '<div class="field_error">' . $this->renderer->humanize($message) . '</div>';
        }

        return $string;
    }

    public function form_rows(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            if (!empty($child->vars['hidden'])) {
                $child->setRendered();
                continue;
            }
            if (    array_key_exists('start_fieldset', $child->vars)
                && $child->vars['start_fieldset'] !== null) {
                if (!empty($child->vars['start_fieldset']['css_group'])) {
                    $class = $child->vars['start_fieldset']['css_group'];
                } else {
                    $class = $child->vars['name'];
                }
                $string .= '<fieldset class="fieldset ' . $class . '">';
                if (!empty($child->vars['start_fieldset']['title'])) {
                    $string .= '<legend>' . $this->renderer->humanize($child->vars['start_fieldset']['title']) . '</legend>';
                }
            }
            $string .= $this->renderer->row($child);
            if (    array_key_exists('end_fieldset', $child->vars)
                && $child->vars['end_fieldset'] !== null) {
                $end_fieldsets = max(1, (int) $child->vars['end_fieldset']);
                for ($i = 0; $i < $end_fieldsets; $i++) {
                    $string .= '</fieldset>';
                }
            }
        }
        return $string;
    }

    public function form_end(FormView $view, array $data)
    {
        $string = '';
        if (   !isset($data['render_rest'])
            || $data['render_rest']) {
            $string .= $this->renderer->rest($view);
        }
        return $string . '</form>';
    }

    public function form_row(FormView $view, array $data)
    {
        $class = 'element element_' . $view->vars['block_prefixes'][count($view->vars['block_prefixes']) - 2];

        if ($view->vars['required']) {
            $class .= ' required';
        }

        if ($data['errors']->count() > 0) {
            $class .= ' error';
        }

        $string = '<div class="' . $class . '">';
        $string .= $this->renderer->label($view);
        $string .= '<div class="input">';
        $string .= $this->renderer->errors($view);
        $string .= $this->renderer->widget($view);
        return $string . '</div></div>';
    }

    public function autocomplete_row(FormView $view, array $data)
    {
        $class = 'element element_' . $view->vars['block_prefixes'][count($view->vars['block_prefixes']) - 2];

        if ($view->vars['required']) {
            $class .= ' required';
        }

        if ($data['errors']->count() > 0) {
            $class .= ' error';
        }

        $string = '<div class="' . $class . '">';
        $string .= $this->renderer->label($view);
        $string .= '<div class="input">';

        if ($data['errors']->count() > 0) {
            $string .= $this->renderer->errors($view) . '<br>';
        }
        $string .= $this->renderer->widget($view);
        return $string . '</div></div>';
    }

    public function button_row(FormView $view, array $data)
    {
        $string = '<div>';
        $string .= $this->renderer->widget($view);
        return $string . '</div>';
    }

    public function hidden_row(FormView $view, array $data)
    {
        return $this->renderer->widget($view);
    }

    public function toolbar_row(FormView $view, array $data)
    {
        $string = '<div class="form_toolbar">';
        foreach ($view as $child) {
            $string .= $this->renderer->widget($child);
        }
        return $string . '</div>';
    }

    public function repeated_row(FormView $view, array $data)
    {
        $string = '';
        foreach ($view->children as $name => $child) {
            if ($name === 'first' && !empty($view->vars['errors'])) {
                $view->children['first']->vars['errors'] = $view->vars['errors'];
            }
            $string .= $this->form_row($child, $data);
        }
        return $string;
    }

    public function attachment_row(FormView $view, array $data)
    {
        $string = '<fieldset' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= '<legend>';
        $string .= (!empty($data['value']['filename'])) ? $data['value']['filename'] : $this->renderer->humanize('add new file');
        $string .= '</legend>';

        $string .= '<div class="attachment-container">';
        $string .= '<div class="attachment-preview">';
        if (!empty($data['value']['size_x']) && !empty($data['value']['size_y'])) {
            $string .= '<a href="' . $data['value']['url'] . '" target="_blank"><img src="' . $data['value']['url'] . '" class="preview-image">';
            $size = "{$data['value']['size_x']}&times;{$data['value']['size_y']}";
            $string .= "<br><span title=\"{$data['value']['guid']}\">{$size}, {$data['value']['formattedsize']}</span></a>";
        } elseif (!empty($data['value']['filename'])) {
            $parts = explode('.', $data['value']['filename']);
            $ext = '';
            if (count($parts) > 1) {
                $ext = end($parts);
            }

            $string .= '<a href="' . $data['value']['url'] . '" target="_blank" class="icon" title="' . $data['value']['filename'] . '">';
            $string .= '<i class="fa fa-file-o"></i><span class="extension">' . $ext . '</span></a>';
        } else {
            $string .= '<span class="icon no-file"><i class="fa fa-file-o"></i></span>';
        }

        $string .= '</div><div class="attachment-input">';
        foreach ($view->children as $child) {
            $string .= $this->renderer->row($child);
        }

        $string .= '</div></div>';
        $string .= $this->jsinit('dm_attachment_init("' . $data['form']['file']->vars['id'] . '")');
        return $string . '</fieldset>';
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        $type = isset($data['type']) ? $data['type'] : 'text';
        if (!empty($data['attr']['readonly']) && $type !== 'hidden') {
            return $data['value'] . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }

        if (!empty($data['attr']['class'])) {
            $view->vars['attr']['class'] = $data['attr']['class'];
        } elseif ($type == 'text' || $type == 'password' || $type == 'email' || $type == 'url') {
            $view->vars['attr']['class'] = 'shorttext';
        }

        $string = '<input type="' . $type . '"';
        $string .= $this->renderer->block($view, 'widget_attributes');
        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            $string .= ' value="' . $this->escape($data['value']) . '"';
        }
        return $string . ' />';
    }

    public function password_widget(FormView $view, array $data)
    {
        // when we come from RepeatedType, type is missing, so we add it
        $data['type'] = 'password';
        if ($data['name'] === 'first') {
            $view->parent->children['second']->vars['label'] = $this->renderer->humanize($view->vars['label']) . ' ' . $this->renderer->humanize($view->parent->children['second']->vars['label']);
        }
        return $this->form_widget_simple($view, $data);
    }

    public function button_widget(FormView $view, array $data)
    {
        $type = isset($data['type']) ? $data['type'] : 'button';
        if (!$data['label']) {
            $data['label'] = $data['name'];
        }
        return '<button type="' . $type . '" ' . $this->renderer->block($view, 'button_attributes') . '>' . $this->renderer->humanize($data['label']) . '</button>';
    }

    public function hidden_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => isset($data['type']) ? $data['type'] : "hidden"]);
    }

    public function email_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => isset($data['type']) ? $data['type'] : "email"]);
    }

    public function url_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => isset($data['type']) ? $data['type'] : "url"]);
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        $element_id = $view->vars['id'];
        $jsinit = 'window.' . $element_id . '_handler_options = ' . $data['handler_options'] . ";\n";
        $jsinit .= "midcom_helper_datamanager2_autocomplete.create_dm2_widget('{$element_id}_search_input', {$data['min_chars']});\n";

        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .=  $this->renderer->widget($view['selection']);
        $string .= ' ' . $this->renderer->widget($view['search_input']);
        $string .= '</fieldset>';
        return $string . $this->jsinit($jsinit);
    }

    public function radio_widget(FormView $view, array $data)
    {
        $string = '<input type="radio"';
        if (!empty($view->vars['attr']['readonly'])) {
            $view->vars['attr']['disabled'] = true;
        }
        $string .= $this->renderer->block($view, 'widget_attributes');
        if (isset($data['value'])) {
            $string .= ' value="' . $data['value'] . '"';
        }
        if ($data['checked']) {
            $string .= ' checked="checked"';
        }
        return $string . ' />';
    }

    public function checkbox_widget(FormView $view, array $data)
    {
        if (!empty($data['attr']['readonly'])) {
            $string = $this->get_view_renderer()->checkbox_widget($view, $data);
            if ($data['checked']) {
                $string .= $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
            }
            return $string;
        }
        $string = '<input type="checkbox"';
        $string .= $this->renderer->block($view, 'widget_attributes');
        if (isset($data['value'])) {
            $string .= ' value="' . $data['value'] . '"';
        }
        if ($data['checked']) {
            $string .= ' checked="checked"';
        }
        return $string . ' />';
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (!empty($data['attr']['readonly']) && empty($view->vars['multiple'])) {
            $string = $this->get_view_renderer()->choice_widget_collapsed($view, $data);
            return $string . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }
        $string = '<select';
        if (   $data['required']
            && null === $data['placeholder']
            && $data['placeholder_in_choices'] === false
            && $data['multiple'] === false) {
            $data['required'] = false;
        }

        if ($data['multiple']) {
            $string .= ' multiple="multiple"';
        }

        $string .= $this->renderer->block($view, 'widget_attributes', ['required' => $data['required']]) . '>';
        if (null !== $data['placeholder']) {
            $string .= '<option value=""';
            if (   $data['required']
                && empty($data['value'])
                && "0" !== $data['value']) {
                $string .= ' selected="selected"';
            }
            $string .= '>' . $data['placeholder'] . '</option>';
        }
        if (count($data['preferred_choices']) > 0) {
            $string .= $this->renderer->block($view, 'choice_widget_options', ['choices' => $data['preferred_choices']]);
            if (count($data['choices']) > 0 && null !== $data['separator']) {
                $string .= '<option disabled="disabled">' . $data['separator'] . '</option>';
            }
        }
        $string .= $this->renderer->block($view, 'choice_widget_options', ['choices' => $data['choices']]);
        return $string . '</select>';
    }

    public function radiocheckselect_widget(FormView $view, array $data)
    {
        if (isset($view->vars['effective_value'])) {
            if ($view->vars['effective_value']) {
                $label = $this->renderer->humanize('widget privilege: allow');
            } else {
                $label = $this->renderer->humanize('widget privilege: deny');
            }
            $new_label = sprintf($this->renderer->humanize('widget privilege: inherit %s'), $label);
            $view->children[2]->vars['label'] = $new_label;
        }
        return $this->choice_widget_expanded($view, $data);
    }

    public function privilegeselection_widget(FormView $view, array $data)
    {
        $string = '<div class="holder">' . $this->choice_widget_collapsed($view, $data) . '</div>';
        return $string . $this->jsinit($view->vars['jsinit']);
    }

    public function choice_widget_expanded(FormView $view, array $data)
    {
        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        foreach ($view as $child) {
            $string .= $this->renderer->widget($child);
            $string .= '<label for="' . $child->vars['id'] . '">' . $this->renderer->humanize($child->vars['label']) . '</label>';
        }
        return $string . '</fieldset>';
    }

    public function choice_widget_options(FormView $view, array $data)
    {
        $string = '';
        foreach ($data['choices'] as $index => $choice) {
            if (is_array($choice) || $choice instanceof ChoiceGroupView) {
                $string .= '<optgroup label="' . $index . '">';
                $string .= $this->renderer->block($view, 'choice_widget_options', ['choices' => $choice]);
                $string .= '</optgroup>';
            } else {
                $string .= '<option value="' . $choice->value . '"';
                if ($data['is_selected']($choice->value, $data['value'])) {
                    $string .= ' selected="selected"';
                }
                if (!empty($choice->attr)) {
                    $string .= ' ' . $this->attributes($choice->attr);
                }
                $string .= '>' . $this->renderer->humanize($choice->label) . '</option>';
            }
        }
        return $string;
    }

    public function other_widget(FormView $view, array $data)
    {
        $string = '<fieldset' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= $this->renderer->widget($view->children['select']);
        $string .= $this->renderer->humanize($view->children['other']->vars['label']) . ': ' . $this->renderer->widget($view->children['other'], ['attr' => ['class' => 'other']]);
        return $string . '</fieldset>';
    }

    public function captcha_widget(FormView $view, array $data)
    {
        $alt = $this->renderer->humanize('captcha image alt text');
        $string = '<fieldset class="captcha">';
        $string .= "<img src='{$view->vars['captcha_url']}' alt='{$alt}' text='{$alt}' class='captcha'><br>";
        $string .= $this->renderer->humanize('captcha message');
        $data['attr']['class'] = 'captcha';
        $data['value'] = '';
        $string .= $this->form_widget_simple($view, $data);
        return $string . '</fieldset>';
    }

    public function codemirror_widget(FormView $view, array $data)
    {
        //we set required to false, because codemirror doesn't play well with html5 validation..
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes', ['required' => false]) . '>';
        $string .= $data['value'] . '</textarea>';
        if (!empty($data['codemirror_snippet'])) {
            $snippet = str_replace('{$id}', $data['id'], $data['codemirror_snippet']);
            $snippet = str_replace('{$read_only}', 'false', $snippet);
            $string .= $this->jsinit($snippet);
        }
        return $string;
    }

    public function jsdate_widget(FormView $view, array $data)
    {
        $string = '<fieldset' . $this->renderer->block($view, 'widget_container_attributes') . '>';

        if (!empty($data['attr']['readonly'])) {
            $string .= $this->get_view_renderer()->jsdate_widget($view, $data);
            $string .= $this->renderer->widget($view['date'], ['type' => 'hidden']);
            if (isset($view['time'])) {
                $string .= $this->renderer->widget($view['time'], ['type' => 'hidden']);
            }
        } else {
            $string .= $this->renderer->widget($view['date'], ['type' => 'hidden']);
            $string .= $this->renderer->widget($view['input'], ['attr' => ['class' => 'jsdate']]);
            if (isset($view['time'])) {
                $string .= ' '. $this->renderer->widget($view['time']);
            }
            $string .= $data['jsinit'];
        }
        return $string . '</fieldset>';
    }

    public function image_widget(FormView $view, array $data)
    {
        $string = '<div' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= '<table class="midcom_datamanager_table_photo"><tr><td>';
        $preview = null;
        $objects = $data['value']['objects'];
        foreach ($objects as $identifier => $info) {
            $preview = $info;
            if ($identifier == 'thumbnail') {
                break;
            }
        }
        if (!empty($preview)) {
            if ($preview['id'] === 0) {
                $preview['url'] = \midcom_connection::get('self') . 'midcom-exec-midcom.datamanager/preview-tmpfile.php?identifier=' . substr($preview['identifier'], strlen('tmpfile-'));
            }

            $string .= '<img src="' . $preview['url'] . '" class="preview-image">';
        }
        $string .= '</td><td>';

        if (!empty($objects)) {
            $string .= '<label class="midcom_datamanager_photo_label">' . $this->renderer->humanize('delete photo') . ' ' . $this->renderer->widget($data['form']['delete']) . '</label>';
            $string .= '<ul>';
            foreach ($objects as $identifier => $info) {
                if (   $info['size_x']
                    && $info['size_y']) {
                    $size = "{$info['size_x']}&times;{$info['size_y']}";
                } else {
                    $size = 'unknown';
                }
                $string .= "<li title=\"{$info['guid']}\"><a href='{$info['url']}' target='_new'>{$info['filename']}:</a>
                {$size}, {$info['formattedsize']}</li>";
            }
            $string .= '</ul>';
        }
        $string .= $this->renderer->widget($data['form']['file']);

        if (array_key_exists('title', $view->children)) {
            $view->children['title']->vars['attr']['placeholder'] = $this->renderer->humanize('title');
            $string .= $this->renderer->widget($view->children['title']);
        }
        $string .= '</td></tr></table></div>';
        $string .= $this->renderer->row($data['form']['identifier']);

        return $string . $this->jsinit('init_image_widget("' . $view->vars['id'] .'");');
    }

    public function subform_widget(FormView $view, array $data)
    {
        $view->vars['attr']['data-prototype'] = $this->escape($this->renderer->row($view->vars['prototype']));
        $view->vars['attr']['data-max-count'] = $view->vars['max_count'];
        $string = $this->renderer->widget($data['form'], $view->vars);
        return $string . $this->jsinit('init_subform("' . $view->vars['id'] . '", ' . $view->vars['sortable'] . ');');
    }

    public function submit_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'button_widget', ['type' => isset($data['type']) ? $data['type'] : 'submit']);
    }

    public function delete_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'button_widget', ['type' => isset($data['type']) ? $data['type'] : 'delete']);
    }

    public function textarea_widget(FormView $view, array $data)
    {
        if (!empty($data['attr']['readonly'])) {
            $view->vars['output_mode'] = 'nl2br';
            $string = $this->get_view_renderer()->text_widget($view, $data);
            return $string . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }
        $view->vars['attr']['class'] = 'longtext';
        $view->vars['attr']['cols'] = 50;
        return '<textarea' . $this->renderer->block($view, 'widget_attributes') . '>' . $data['value'] . '</textarea>';
    }

    public function markdown_widget(FormView $view, array $data)
    {
        $string = '<textarea' . $this->renderer->block($view, 'widget_attributes', ['required' => false]) . '>' . $data['value'] . '</textarea>';
        return $string . $this->jsinit('var simplemde = new SimpleMDE({ element: document.getElementById("' . $view->vars['id'] . '"), status: false });');
    }

    public function tinymce_widget(FormView $view, array $data)
    {
        if (!empty($data['attr']['readonly'])) {
            $string = $this->get_view_renderer()->text_widget($view, $data);
            return $string . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }
        //we set required to false, because tinymce doesn't play well with html5 validation..
        $string = '<textarea' . $this->renderer->block($view, 'widget_attributes', ['required' => false]) . '>' . $data['value'] . '</textarea>';
        return $string . $this->jsinit($data['tinymce_snippet']);
    }

    public function form_label(FormView $view, array $data)
    {
        if ($data['label'] === false) {
            return '';
        }
        if (!$data['label']) {
            $data['label'] = $data['name'];
        }
        $data['label'] = $this->renderer->humanize($data['label']);

        $label_attr = $data['label_attr'];
        if ($data['required'] && empty($data['attr']['readonly'])) {
            $label_attr['class'] = trim((isset($label_attr['class']) ? $label_attr['class'] : '') . ' required');
            $data['label'] .= ' <span class="field_required_start">*</span>';
        }
        if (!$data['compound']) {
            $label_attr['for'] = $data['id'];
        }
        return '<label' . $this->attributes($label_attr) . '><span class="field_text">' . $data['label'] . '</span></label>';
    }
}
