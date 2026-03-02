<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use midcom;
use midcom_helper_formatter;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use midcom\datamanager\renderer;
use Parsedown;

class view extends base
{
    private bool $skip_empty = false;

    /**
     * Define the quotes behavior when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    private int $specialchars_quotes = ENT_QUOTES;

    /**
     * Define the charset to use when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    private string $specialchars_charset = 'UTF-8';

    public function __construct(renderer $renderer, bool $skip_empty = false)
    {
        parent::__construct($renderer);
        $this->skip_empty = $skip_empty;
    }

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
            if (!empty($child->vars['hidden'])) {
                $child->setRendered();
                continue;
            }

            if (isset($child->vars['start_fieldset'])) {
                $string .= '<div class="fieldset">';
                if (!empty($child->vars['start_fieldset']['title'])) {
                    $string .= '<h2>' . $this->renderer->humanize($child->vars['start_fieldset']['title']) . '</h2>';
                }
            }

            $string .= $this->renderer->row($child);

            if (isset($child->vars['end_fieldset'])) {
                $end_fieldsets = max(1, (int) $child->vars['end_fieldset']);
                $string .= str_repeat('</div>', $end_fieldsets);
            }
        }
        return $string;
    }

    public function form_rest(FormView $view, array $data)
    {
        return '';
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
        $content = $this->renderer->widget($view);
        if ($this->skip_empty && trim($content) === '') {
            return '';
        }

        $class = 'field field_' . $view->vars['block_prefixes'][count($view->vars['block_prefixes']) - 2];

        $string = '<div class="' . $class . '">';
        $string .= $this->renderer->label($view);
        $string .= '<div class="value">';
        $string .= $content;
        return $string . '</div></div>';
    }

    public function attachment_row(FormView $view, array $data)
    {
        if (empty($data['value']['url'])) {
            return '';
        }
        return '<a href="' . $data['value']['url'] . '">' . $data['value']['title'] . '</a>';
    }

    public function form_label(FormView $view, array $data)
    {
        if ($data['label'] === false) {
            return '';
        }
        $label_attr = $data['label_attr'];
        $label_attr['class'] = trim('title ' . ($label_attr['class'] ?? ''));
        if (!$data['label']) {
            $data['label'] = $data['name'];
        }
        return '<div ' . $this->attributes($label_attr) . '>' . $this->renderer->humanize($data['label']) . '</div>';
    }

    public function subform_widget(FormView $view, array $data)
    {
        if (empty($view->vars['data'])) {
            return '';
        }
        $string = '<div ' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= $this->renderer->block($view, 'form_rows');
        $string .= $this->renderer->rest($view);
        return $string . '</div>';
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            return $this->escape($data['value']);
        }
        return '';
    }

    public function email_widget(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            return '<a href="mailto:' . $data['value'] . '">' . $this->escape($data['value']) . '</a>';
        }
        return '';
    }

    public function password_widget(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            return '******';
        }
        return '';
    }

    public function url_widget(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            return '<a href="' . $data['value'] . '">' . $this->escape($data['value']) . '</a>';
        }
        return '';
    }

    public function text_widget(FormView $view, array $data)
    {
        if (empty($view->vars['output_mode'])) {
            $view->vars['output_mode'] = 'specialchars';
        }

        switch ($view->vars['output_mode']) {
            case 'code':
                return '<pre style="overflow:auto">' . htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'pre':
                return '<pre style="white-space: pre-wrap">' . htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'specialchars':
                return htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset);

            case 'nl2br':
                return nl2br(htmlentities($data['value'], $this->specialchars_quotes, $this->specialchars_charset));

            case 'midgard_f':
                return midcom_helper_formatter::format($data['value'], 'f');

            case 'markdown':
                $parsedown = new Parsedown();
                return $parsedown->text($data['value']);

            case (str_starts_with($view->vars['output_mode'], 'x')):
                // Run the contents through a custom formatter registered via mgd_register_filter
                return midcom_helper_formatter::format($data['value'], $view->vars['output_mode']);

            case 'html':
                return $data['value'];
        }
    }

    public function choice_widget_expanded(FormView $view, array $data)
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
        if (!array_key_exists('main', $data['value']['objects'])) {
            $ret = "";
            if (!empty($data['value']['objects'])) {
                $ret .= $this->renderer->humanize('could not figure out which image to show, listing files') . "<ul>";
                foreach ($data['value']['objects'] as $info) {
                    $ret .= "<li><a href='{$info['url']}'>{$info['filename']}</a></li>";
                }
                $ret .= "</ul>";
            }
            return $ret;
        }

        $identifier = 'main';
        $linkto = false;
        if (array_key_exists('view', $data['value']['objects'])) {
            $identifier = 'view';
            $linkto = 'main';
        } elseif (array_key_exists('thumbnail', $data['value']['objects'])) {
            $identifier = 'thumbnail';
            $linkto = 'main';
        } elseif (array_key_exists('archival', $data['value']['objects'])) {
            $linkto = 'archival';
        }

        $img = $data['value']['objects'][$identifier];
        $return = '<div class="midcom_helper_datamanager2_type_photo">';
        $img_tag = "<img src='{$img['url']}' {$img['size_line']} class='photo {$identifier}' />";
        if ($linkto) {
            $linked = $data['value']['objects'][$linkto];
            $return .= "<a href='{$linked['url']}' target='_blank' class='{$linkto} {$linked['mimetype']}'>{$img_tag}</a>";
        } else {
            $return .= $img_tag;
        }
        if (array_key_exists('archival', $data['value']['objects'])) {
            $arch = $data['value']['objects']['archival'];
            $return .= "<br/><a href='{$arch['url']}' target='_blank' class='archival {$arch['mimetype']}'>" . $this->renderer->humanize('archived image') . '</a>';
        }
        return $return . '</div>';
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        return implode(', ', $data['handler_options']['preset']);
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (!empty($view->vars['multiple'])) {
            $selection = $data['data'];
        } else {
            $selection = (string) $data['data'];
        }
        $selected = [];
        foreach ($data['choices'] as $choice) {
            if ($choice instanceof ChoiceGroupView) {
                foreach ($choice->choices as $option) {
                    if ($data['is_selected']($option->value, $selection)) {
                        $selected[] = $this->renderer->humanize($option->label);
                    }
                }
            } elseif ($data['is_selected']($choice->value, $selection)) {
                $selected[] = $this->renderer->humanize($choice->label);
            }
        }
        return implode(', ', $selected);
    }

    public function checkbox_widget(FormView $view, array $data)
    {
        if ($data['checked']) {
            return '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png" alt="selected" />';
        }
        return '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png" alt="not selected" />';
    }

    public function codemirror_widget(FormView $view, array $data)
    {
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes', $data) . '>';
        $string .= $data['value'] . '</textarea>';
        if (!empty($data['codemirror_snippet'])) {
            $this->add_head_elements_for_codemirror($data['modes']);
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
        if (empty($data['value']['date'])) {
            return '';
        }

        $time_format = 'none';
        if (isset($view['time'])) {
            $time_format = (isset($data['value']['seconds'])) ? 'medium' : 'short';
        }
        return midcom::get()->i18n->get_l10n()->get_formatter()->datetime($data['value']['date'], 'medium', $time_format);
    }
}
