<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package midcom.services.rcs
 */
class midcom_services_rcs_renderer_html_sidebyside extends Diff_Renderer_Html_Array
{
    /**
     * Render a and return diff with changes between the two sequences
     * displayed side by side.
     *
     * @return string The generated side by side diff.
     */
    public function render()
    {
        $html = '';
        $changes = parent::render();
        if (empty($changes))
        {
            return $html;
        }
        $ln = midcom::get('i18n')->get_l10n("midcom");
        $html .= '<table class="Differences DifferencesSideBySide">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2">' . $ln->get("Old Version") . '</th>';
        $html .= '<th colspan="2">' . $ln->get("New Version") . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach($changes as $i => $blocks) {
            if($i > 0) {
                $html .= '<tr class="Skipped">';
                $html .= '<th>&hellip;</th><td>&nbsp;</td>';
                $html .= '<th>&hellip;</th><td>&nbsp;</td>';
                $html .= '</tr>';
            }

            foreach($blocks as $change) {
                // Equal changes should be shown on both sides of the diff
                if($change['tag'] == 'equal') {
                    foreach($change['base']['lines'] as $no => $line) {
                        $fromLine = $change['base']['offset'] + $no + 1;
                        $toLine = $change['changed']['offset'] + $no + 1;
                        $this->add_line($html, $fromLine, $line, $toLine, $line);
                    }
                }
                // Added lines only on the right side
                else if($change['tag'] == 'insert') {
                    foreach($change['changed']['lines'] as $no => $line) {
                        $toLine = $change['changed']['offset'] + $no + 1;
                        $this->add_line($html, '&nbsp;', '&nbsp;', $toLine, '<ins>' . $line . '</ins>');
                    }
                }
                // Show deleted lines only on the left side
                else if($change['tag'] == 'delete') {
                    foreach($change['base']['lines'] as $no => $line) {
                        $fromLine = $change['base']['offset'] + $no + 1;
                        $this->add_line($html, $fromLine, '<del>' . $line . '</del>');
                    }
                }
                // Show modified lines on both sides
                else if($change['tag'] == 'replace') {
                    if(count($change['base']['lines']) >= count($change['changed']['lines'])) {
                        foreach($change['base']['lines'] as $no => $line) {
                            $fromLine = $change['base']['offset'] + $no + 1;
                            if(!isset($change['changed']['lines'][$no])) {
                                $toLine = '&nbsp;';
                                $changedLine = '&nbsp;';
                            }
                            else {
                                $line = '<del>' . $line . '</del>';
                                $toLine = $change['base']['offset'] + $no + 1;
                                $changedLine = '<ins>'.$change['changed']['lines'][$no].'</ins>';
                            }

                            $this->add_line($html, $fromLine, $line, $toLine, $changedLine);
                        }
                    }
                    else {
                        foreach($change['changed']['lines'] as $no => $changedLine) {
                            if(!isset($change['base']['lines'][$no])) {
                                $fromLine = '&nbsp;';
                                $line = '<span>&nbsp;</span>';
                            }
                            else {
                                $fromLine = $change['base']['offset'] + $no + 1;
                                $line = '<span>'.$change['base']['lines'][$no].'</span>';
                            }
                            $toLine = $change['changed']['offset'] + $no + 1;
                            $this->add_line($html, $fromLine, $line, $toLine, $changedLine);
                        }
                    }
                }
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    private function add_line(&$html, $from_no, $from_content, $to_no = '&nbsp;', $to_content = '&nbsp;')
    {
        $html .= '<tr>';
        $html .= '<th>' . $from_no . '</th>';
        $html .= '<td class="Left">' . $from_content . '&nbsp;</td>';
        $html .= '<th>' . $to_no . '</th>';
        $html .= '<td class="Right">' . $to_content . '</td>';
        $html .= '</tr>';
    }
}
?>