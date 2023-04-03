<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use midcom\datamanager\renderer;
use midcom;
use midcom\datamanager\helper\autocomplete;

class form extends base
{
    public function __construct(renderer $renderer)
    {
        parent::__construct($renderer);
        midcom::get()->head->prepend_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/default.css');
    }

    private function get_view_renderer() : view
    {
        return new view($this->renderer);
    }

    public function form_start(FormView $view, array $data)
    {
        $attributes = array_replace([
            'id' => $data['name'],
            'name' => $data['name'],
            'method' => strtolower($data['method']),
            'action' => $data['action'],
            'class' => 'datamanager2'
        ], $data['attr']);

        if ($data['multipart']) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        return '<form ' . $this->attributes($attributes) . '>';
    }

    public function form_errors(FormView $view, array $data)
    {
        $errors = [];
        foreach ($data['errors'] as $error) {
            $params = $error->getMessageParameters();
            $message = $error->getMessage();
            foreach ($params as $param) {
                if (str_contains($message, (string) $param)) {
                    $message = str_replace($param, $this->renderer->humanize($param), $message);
                }
            }
            $errors[] = '<div class="field_error">' . $this->renderer->humanize($message) . '</div>';
        }

        return implode('', $errors);
    }

    public function form_rows(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            if (!empty($child->vars['hidden'])) {
                $child->setRendered();
                continue;
            }
            if ($child->vars['start_fieldset'] !== null) {
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
            if ($child->vars['end_fieldset'] !== null) {
                $end_fieldsets = max(1, (int) $child->vars['end_fieldset']);
                $string .= str_repeat('</fieldset>', $end_fieldsets);
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
        foreach ($view as $key => $child) {
            $label = $this->renderer->humanize($view->vars['button-labels'][$key]);
            $string .= $this->renderer->widget($child, ['label' => $label]);
        }
        return $string . '</div>';
    }

    public function repeated_row(FormView $view, array $data)
    {
        $view->children['second']->vars['label'] = $this->renderer->humanize($view->children['first']->vars['label']) . ' ' . $this->renderer->humanize($view->children['second']->vars['label']);
        $string = '';
        foreach ($view->children as $child) {
            $string .= $this->form_row($child, $data);
        }
        return $string;
    }

    public function attachment_row(FormView $view, array $data)
    {
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/attachment.js');

        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= '<legend>';
        $string .= (!empty($data['value']['filename'])) ? $data['value']['filename'] : $this->renderer->humanize('add new file');
        $string .= '</legend>';

        $string .= '<div class="attachment-container">';
        $string .= '<div class="attachment-preview">';
        if (!empty($data['value']['filename'])) {
            $ext = pathinfo($data['value']['filename'], PATHINFO_EXTENSION);
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

    public function image_row(FormView $view, array $data)
    {
        if (!in_array('subform', $view->parent->vars['block_prefixes'])) {
            return $this->form_row($view, $data);
        }

        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= '<legend>';
        $string .= (!empty($data['value']['objects']['main']['filename'])) ? preg_replace('/^.+?-/', '', $data['value']['objects']['main']['filename']) : $this->renderer->humanize('add new file');
        $string .= '</legend>';

        $string .= $this->renderer->widget($view);

        return $string . '</fieldset>';
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        $type = $data['type'] ?? 'text';
        if ($view->vars['readonly'] && $type !== 'hidden') {
            $vr = $this->get_view_renderer();
            if ($type == 'text' && ($data['attr']['inputmode'] ?? null) == 'url') {
                // see https://github.com/symfony/symfony/issues/29690
                $method = 'url_widget';
            } else {
                $method = $type . '_widget';
            }
            if (is_callable([$vr, $method])) {
                $value = $vr->$method($view, $data);
            } else {
                $value = $data['value'];
            }

            $data['type'] = 'hidden';
            return $value . $this->renderer->block($view, 'form_widget_simple', $data);
        }

        if (empty($data['attr']['class']) && in_array($type, ['text', 'password', 'email', 'url'])) {
            $data['attr']['class'] = 'shorttext';
        }

        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            $data['attr']['value'] = $data['value'];
        }
        $data['attr']['type'] = $type;
        return '<input ' . $this->renderer->block($view, 'widget_attributes', $data) . ' />';
    }

    public function button_widget(FormView $view, array $data)
    {
        $type = $data['type'] ?? 'button';
        if (!$data['label']) {
            $data['label'] = $data['name'];
        }

        return '<button type="' . $type . '" ' . $this->renderer->block($view, 'button_attributes') . '>' . $this->renderer->humanize($data['label']) . '</button>';
    }

    public function hidden_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => $data['type'] ?? "hidden"]);
    }

    public function email_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => $data['type'] ?? "email"]);
    }

    public function color_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => $data['type'] ?? "color"]);
    }

    public function password_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => $data['type'] ?? "password"]);
    }

    public function url_widget(FormView $view, array $data)
    {
        return $this->renderer->block($view, 'form_widget_simple', ['type' => $data['type'] ?? "url"]);
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        if ($view->vars['readonly']) {
            $string = $this->get_view_renderer()->autocomplete_widget($view, $data);
            return $string .  $this->renderer->widget($view['selection']);
        }

        autocomplete::add_head_elements($data['handler_options']['creation_mode_enabled'], $data['handler_options']['sortable']);

        $element_id = $view->vars['id'];
        $jsinit = 'window.' . $element_id . '_handler_options = ' . json_encode($data['handler_options']) . ";\n";
        $jsinit .= "midcom_helper_datamanager2_autocomplete.create_dm2_widget('{$element_id}_search_input', {$data['min_chars']});\n";

        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .=  $this->renderer->widget($view['selection']);
        $string .= ' ' . $this->renderer->widget($view['search_input']);
        $string .= '</fieldset>';
        return $string . $this->jsinit($jsinit);
    }

    protected function prepare_widget_attributes(string $type, array &$data)
    {
        $data['attr']['type'] = $type;
        if (isset($data['value'])) {
            $data['attr']['value'] = $data['value'];
        }
        if ($data['checked']) {
            $data['attr']['checked'] = 'checked';
        }
    }

    public function radio_widget(FormView $view, array $data)
    {
        $this->prepare_widget_attributes('radio', $data);
        if ($view->vars['readonly']) {
            $data['attr']['disabled'] = true;
        }

        return '<input ' . $this->renderer->block($view, 'widget_attributes', $data) . ' />';
    }

    public function checkbox_widget(FormView $view, array $data)
    {
        if ($view->vars['readonly']) {
            $string = $this->get_view_renderer()->checkbox_widget($view, $data);
            if ($data['checked']) {
                $string .= $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
            }
            return $string;
        }
        $this->prepare_widget_attributes('checkbox', $data);

        return '<input ' . $this->renderer->block($view, 'widget_attributes', $data) . ' />';
    }

    private function collect_choices(array $input, array &$choices)
    {
        foreach ($input as $choice) {
            if ($choice instanceof ChoiceGroupView) {
                $this->collect_choices($choice->getIterator()->getArrayCopy(), $choices);
            } elseif (is_array($choice)) {
                $this->collect_choices($choice, $choices);
            } else {
                $choices[] = $choice->value;
            }
        }
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if ($view->vars['readonly'] && empty($view->vars['multiple'])) {
            $string = $this->get_view_renderer()->choice_widget_collapsed($view, $data);
            return $string . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }

        $string = '<select ';
        if (   $data['required']
            && null === $data['placeholder']
            && $data['placeholder_in_choices'] === false
            && $data['multiple'] === false) {
            $data['required'] = false;
        }

        if ($data['multiple']) {
            $data['attr']['multiple'] = 'multiple';
        }

        $string .= $this->renderer->block($view, 'widget_attributes', $data) . '>';
        if (null !== $data['placeholder']) {
            $string .= '<option value=""';
            if (   $data['required']
                && empty($data['value'])
                && "0" !== $data['value']) {
                $string .= ' selected="selected"';
            }
            $string .= '>' . $data['placeholder'] . '</option>';
        }
        $available_values = [];
        if (count($data['preferred_choices']) > 0) {
            $this->collect_choices($data['preferred_choices'], $available_values);
            $string .= $this->renderer->block($view, 'choice_widget_options', ['choices' => $data['preferred_choices']]);
            if (count($data['choices']) > 0 && null !== $data['separator']) {
                $string .= '<option disabled="disabled">' . $data['separator'] . '</option>';
            }
        }

        $string .= $this->renderer->block($view, 'choice_widget_options', ['choices' => $data['choices']]);

        $this->collect_choices($data['choices'], $available_values);

        if ($data['value']) {
            foreach ((array) $data['value'] as $selected) {
                if (!in_array($selected, $available_values)) {
                    $string .= '<option ' . $this->attributes(['value' => $selected, 'selected' => 'selected']) . '>' . $selected . '</option>';
                }
            }
        }

        return $string . '</select>';
    }

    public function privilege_widget(FormView $view, array $data)
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
        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.css');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.js');

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
                $choice->attr['value'] = $choice->value;
                if ($data['is_selected']($choice->value, $data['value'])) {
                    $choice->attr['selected'] = 'selected';
                } else {
                    unset($choice->attr['selected']);
                }

                $string .= '<option ' . $this->attributes($choice->attr);
                $string .= '>' . $this->renderer->humanize($choice->label) . '</option>';
            }
        }
        return $string;
    }

    public function other_widget(FormView $view, array $data)
    {
        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= $this->renderer->widget($view->children['select']);
        $string .= $this->renderer->humanize($view->children['other']->vars['label']) . ': ' . $this->renderer->widget($view->children['other'], ['attr' => ['class' => 'other']]);
        return $string . '</fieldset>';
    }

    public function captcha_widget(FormView $view, array $data)
    {
        $alt = $this->renderer->humanize('captcha image alt text');
        $string = '<fieldset class="captcha">';
        $string .= "<img src='{$view->vars['captcha_url']}' alt='{$alt}' title='{$alt}' class='captcha'><br>";
        $string .= $this->renderer->humanize('captcha message');
        $data['attr']['class'] = 'captcha';
        $data['value'] = '';
        $string .= $this->form_widget_simple($view, $data);
        return $string . '</fieldset>';
    }

    public function codemirror_widget(FormView $view, array $data)
    {
        // we set required to false, because codemirror doesn't play well with html5 validation..
        $data['required'] = false;
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes', $data) . '>';
        $string .= $data['value'] . '</textarea>';
        if (!empty($data['codemirror_snippet'])) {
            $this->add_head_elements_for_codemirror($data['modes']);
            $snippet = str_replace('{$id}', $data['id'], $data['codemirror_snippet']);
            $snippet = str_replace('{$read_only}', 'false', $snippet);
            $string .= $this->jsinit($snippet);
        }
        return $string;
    }

    public function jsdate_widget(FormView $view, array $data)
    {
        $string = '<fieldset ' . $this->renderer->block($view, 'widget_container_attributes') . '>';

        if ($view->vars['readonly']) {
            $string .= $this->get_view_renderer()->jsdate_widget($view, $data);
            $string .= $this->renderer->widget($view['date'], ['type' => 'hidden']);
            if (isset($view['time'])) {
                $string .= $this->renderer->widget($view['time'], ['type' => 'hidden']);
            }
        } else {
            midcom::get()->head->enable_jquery_ui(['datepicker']);
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/datepicker.js');

            $string .= $this->renderer->widget($view['date'], ['type' => 'hidden']);
            $string .= $this->renderer->widget($view['input'], ['attr' => ['class' => 'jsdate']]);
            if (isset($view['time'])) {
                $string .= ' ' . $this->renderer->widget($view['time']);
            }
            $string .= $data['jsinit'];
        }
        return $string . '</fieldset>';
    }

    public function image_widget(FormView $view, array $data)
    {
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/image.css');
        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/image.js');

        $string = '<div ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= '<table class="midcom_datamanager_table_photo"><tr><td>';
        $preview = null;
        $objects = $data['value']['objects'] ?? [];
        foreach ($objects as $identifier => $info) {
            $preview = $info;
            if ($identifier == 'thumbnail') {
                break;
            }
        }
        if (!empty($preview)) {
            if ($preview['id'] === 0) {
                $preview['url'] = \midcom_connection::get_url('self') . 'midcom-exec-midcom.datamanager/preview-tmpfile.php?identifier=' . substr($preview['identifier'], strlen('tmpfile-'));
            }

            $string .= '<img src="' . $preview['url'] . '" class="preview-image">';
        }
        $string .= '</td><td>';

        if (!empty($objects)) {
            $string .= '<label class="midcom_datamanager_photo_label">' . $this->renderer->humanize('delete photo') . ' ' . $this->renderer->widget($data['form']['delete']) . '</label>';
            $string .= '<ul>';
            foreach ($objects as $info) {
                if (   $info['size_x']
                    && $info['size_y']) {
                    $size = "{$info['size_x']}&times;{$info['size_y']}";
                } else {
                    $size = 'unknown';
                }
                $string .= "<li title=\"{$info['guid']}\">{$size}: <a href='{$info['url']}' target='_new'>{$info['formattedsize']}</a></li>";
            }
            $string .= '</ul>';
        }
        $string .= $this->renderer->errors($data['form']['file']);
        $string .= $this->renderer->widget($data['form']['file']);

        if (array_key_exists('title', $view->children)) {
            $string .= $this->renderer->widget($view->children['title'], ['attr' => ['placeholder' => $this->renderer->humanize('title')]]);
        }
        if (array_key_exists('description', $view->children)) {
            $string .= $this->renderer->widget($view->children['description'], ['attr' => ['placeholder' => $this->renderer->humanize('description')]]);
        }
        if (array_key_exists('score', $view->children)) {
            $string .= $this->renderer->widget($view->children['score']);
        }
        $string .= '</td></tr></table></div>';
        $string .= $this->renderer->row($data['form']['identifier']);

        return $string . $this->jsinit('init_image_widget("' . $view->vars['id'] .'");');
    }

    public function subform_widget(FormView $view, array $data)
    {
        $head = midcom::get()->head;
        $head->enable_jquery();
        if ($view->vars['sortable'] == 'true') {
            $head->enable_jquery_ui(['mouse', 'sortable']);
        }
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/subform.js');
        if (array_key_exists('prototype', $view->vars)) {
            $data['attr']['data-prototype'] = $this->escape($this->renderer->row($view->vars['prototype']));
        }
        $data['attr']['data-max-count'] = $view->vars['max_count'];
        $string = $this->renderer->widget($data['form'], $data);
        return $string . $this->jsinit('init_subform("' . $view->vars['id'] . '", ' . $view->vars['sortable'] . ', ' . $view->vars['allow_add'] . ', ' . $view->vars['allow_delete'] . ');');
    }

    public function submit_widget(FormView $view, array $data)
    {
        $data['type'] = $data['type'] ?? 'submit';
        return $this->renderer->block($view, 'button_widget', $data);
    }

    public function delete_widget(FormView $view, array $data)
    {
        $data['type'] = $data['type'] ?? 'delete';
        return $this->renderer->block($view, 'button_widget', $data);
    }

    public function textarea_widget(FormView $view, array $data)
    {
        if ($view->vars['readonly']) {
            $view->vars['output_mode'] = 'nl2br';
            $string = $this->get_view_renderer()->text_widget($view, $data);
            return $string . $this->renderer->block($view, 'form_widget_simple', ['type' => "hidden"]);
        }
        $data['attr']['class'] = 'longtext';
        $data['attr']['cols'] = 50;
        return '<textarea ' . $this->renderer->block($view, 'widget_attributes', $data) . '>' . $data['value'] . '</textarea>';
    }

    public function markdown_widget(FormView $view, array $data)
    {
        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/easymde-2.16.1/easymde.min.css');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/easymde-2.16.1/easymde.min.js');

        $data['required'] = false;
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes', $data) . '>' . $data['value'] . '</textarea>';
        return $string . $this->jsinit('var easymde = new EasyMDE({ element: document.getElementById("' . $view->vars['id'] . '"), status: false });');
    }

    public function tinymce_widget(FormView $view, array $data)
    {
        if ($view->vars['readonly']) {
            $string = $this->get_view_renderer()->text_widget($view, $data);
            $data['type'] = 'hidden';
            return $string . $this->renderer->block($view, 'form_widget_simple', $data);
        }

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile($data['tinymce_url']);
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/tinymce.custom.js');

        // we set required to false, because tinymce doesn't play well with html5 validation..
        $data['required'] = false;
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes', $data) . '>' . $data['value'] . '</textarea>';
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
        if ($data['required'] && !$view->vars['readonly']) {
            $label_attr['class'] = trim(($label_attr['class'] ?? '') . ' required');
            $data['label'] .= ' <span class="field_required_start">*</span>';
        }
        if (!$data['compound']) {
            $label_attr['for'] = $data['id'];
        }
        return '<label ' . $this->attributes($label_attr) . '><span class="field_text">' . $data['label'] . '</span></label>';
    }
}
