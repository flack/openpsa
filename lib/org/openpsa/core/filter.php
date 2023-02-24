<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class that encapsulates a single query filter
 *
 * @package org.openpsa.core
 */
abstract class org_openpsa_core_filter
{
    /**
     * The filter's unique name
     */
    public string $name;

    protected array $_selection = [];

    protected string $_label;

    /**
     * Apply filter to given query
     */
    abstract public function apply(array $selection, midcom_core_query $query);

    public function add_head_elements()
    {
    }

    /**
     * Renders the filter widget according to mode
     */
    abstract public function render();

    public function set_label($label)
    {
        $this->_label = $label;
    }

    protected function _render_actions()
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.core');
        echo '<i class="fa fa-check filter_action filter_apply" title="' . $l10n->get("apply") . '"></i>';
        echo '<i class="fa fa-times-circle filter_action filter_unset" title="' . $l10n->get("unset") . '"></i>';
    }
}
