<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Form;

/**
 * Experimental renderer class
 */
class renderer extends FormRenderer
{
    public function set_template(FormView $view, template\base $template)
    {
        $this->getEngine()->setTheme($view, $template);
    }

    public function start(FormView $view, array $attributes = array())
    {
        return $this->renderBlock($view, 'form_start', $attributes);
    }

    public function end(FormView $view, array $attributes = array())
    {
        return $this->renderBlock($view, 'form_end', $attributes);
    }

    public function widget(FormView $view, array $attributes = array())
    {
        return $this->searchAndRenderBlock($view, 'widget', $attributes);
    }

    public function block(FormView $view, $name, array $attributes = array())
    {
        return $this->renderBlock($view, $name, $attributes);
    }

    public function rest(FormView $view, array $attributes = array())
    {
        return $this->searchAndRenderBlock($view, 'rest', $attributes);
    }

    public function errors(FormView $view, array $attributes = array())
    {
        return $this->searchAndRenderBlock($view, 'errors', $attributes);
    }

    public function row(FormView $view, array $attributes = array())
    {
        return $this->searchAndRenderBlock($view, 'row', $attributes);
    }

    public function label(FormView $view, array $attributes = array())
    {
        return $this->searchAndRenderBlock($view, 'label', $attributes);
    }
}