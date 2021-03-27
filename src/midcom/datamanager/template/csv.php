<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;

class csv extends plaintext
{
    public function checkbox_widget(FormView $view, array $data)
    {
        return ($data['checked']) ? '1' : '0';
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
