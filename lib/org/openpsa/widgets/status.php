<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.widgets
 */
abstract class org_openpsa_widgets_status
{
    public function render()
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.widgets');
        $l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
        echo '<div class="area org_openpsa_helper_box history status">';
        echo "<h3>" . $l10n->get('status history');
        echo $this->get_button();
        echo "</h3>\n";

        echo "<div class=\"current-status {$this->get_status_class()}\">";
        echo $l10n->get('status') . ': ' . $this->get_current_status();
        echo '</div>';

        echo "<ul>\n";
        foreach ($this->get_history() as $entry)
        {
            echo '<li><div class="date">' . date($l10n_midcom->get('short date') . ' H:i', $entry['timestamp']) . '</div>';
            echo $entry['message'];
        }
        echo "</ul>\n";

        echo '</div>';
    }

    abstract public function get_button();

    abstract public function get_current_status();

    abstract public function get_status_class();

    abstract public function get_history();
}