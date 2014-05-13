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
    private $html = '';

    /**
     * Render a and return diff with changes between the two sequences
     * displayed side by side.
     *
     * @return string The generated side by side diff.
     */
    public function render()
    {
        $changes = parent::render();
        if (empty($changes))
        {
            return $this->html;
        }

        $this->html .= '<table class="Differences DifferencesSideBySide">';
        $this->html .= '<thead>';
        $this->html .= '<tr>';
        $this->html .= '<th colspan="2">' . $this->options['old'] . '</th>';
        $this->html .= '<th colspan="2">' . $this->options['new'] . '</th>';
        $this->html .= '</tr>';
        $this->html .= '</thead>';
        $this->html .= '<tbody>';

        foreach($changes as $i => $blocks) {
            if($i > 0) {
                $this->html .= '<tr class="Skipped">';
                $this->html .= '<th>&hellip;</th><td>&nbsp;</td>';
                $this->html .= '<th>&hellip;</th><td>&nbsp;</td>';
                $this->html .= '</tr>';
            }

            foreach ($blocks as $change)
            {
                $from_offset = $change['base']['offset'] + 1;
                $to_offset = $change['changed']['offset'] + 1;

                // Equal changes should be shown on both sides of the diff
                if ($change['tag'] == 'equal') {
                    foreach($change['base']['lines'] as $no => $line) {
                        $this->add_line($change['tag'], $from_offset + $no, $line, $to_offset + $no, $line);
                    }
                }
                // Added lines only on the right side
                else if($change['tag'] == 'insert') {
                    foreach($change['changed']['lines'] as $no => $line) {
                        $this->add_line($change['tag'], '&nbsp;', '&nbsp;', $to_offset + $no, '<ins>' . $line . '</ins>');
                    }
                }
                // Show deleted lines only on the left side
                else if($change['tag'] == 'delete') {
                    foreach($change['base']['lines'] as $no => $line) {
                        $this->add_line($change['tag'], $from_offset + $no, '<del>' . $line . '</del>');
                    }
                }
                // Show modified lines on both sides
                else if($change['tag'] == 'replace') {
                    if(count($change['base']['lines']) >= count($change['changed']['lines'])) {
                        foreach($change['base']['lines'] as $no => $line) {
                            if(!isset($change['changed']['lines'][$no])) {
                                $toLine = '&nbsp;';
                                $changedLine = '&nbsp;';
                            }
                            else {
                                $line = '<del>' . $line . '</del>';
                                $toLine = $to_offset + $no;
                                $changedLine = '<ins>'.$change['changed']['lines'][$no].'</ins>';
                            }

                            $this->add_line($change['tag'], $from_offset + $no, $line, $toLine, $changedLine);
                        }
                    }
                    else {
                        foreach($change['changed']['lines'] as $no => $changedLine) {
                            if(!isset($change['base']['lines'][$no])) {
                                $fromLine = '&nbsp;';
                                $line = '<span>&nbsp;</span>';
                            }
                            else {
                                $fromLine = $from_offset + $no;
                                $line = '<span>'.$change['base']['lines'][$no].'</span>';
                            }
                            $this->add_line($change['tag'], $fromLine, $line, $to_offset + $no, $changedLine);
                        }
                    }
                }
            }
        }
        $this->html .= '</tbody>';
        $this->html .= '</table>';
        return $this->html;
    }

    private function add_line($tag, $from_no, $from_content, $to_no = '&nbsp;', $to_content = '&nbsp;')
    {
        $this->html .= '<tr class="' . $tag . '">';
        $this->html .= '<th>' . $from_no . '</th>';
        $this->html .= '<td class="Left">' . $from_content . '&nbsp;</td>';
        $this->html .= '<th>' . $to_no . '</th>';
        $this->html .= '<td class="Right">' . $to_content . '</td>';
        $this->html .= '</tr>';
    }
}
?>