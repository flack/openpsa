<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package midcom.services.rcs
 */
class midcom_services_rcs_renderer_html_sidebyside extends Diff_Renderer_Html_SideBySide
{
    /**
     * Render a and return diff with changes between the two sequences
     * displayed side by side.
     *
     * @return string The generated side by side diff.
     */
    public function render()
    {
        $changes = parent::render();
        if (!empty($changes))
        {
            $html = '<table class="Differences DifferencesSideBySide">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="2">' . $this->options['old'] . '</th>';
            $html .= '<th colspan="2">' . $this->options['new'] . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $changes = preg_replace('/^<table.+?<\/thead>/', $html, $changes);
        }
        return $changes;
    }
}
