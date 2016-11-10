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
     *
     * @var string
     */
    public $name;

    /**
     * The filter selection, if any
     *
     * @var array
     */
    protected $_selection = array();

    /**
     * The filter label
     *
     * @var string
     */
    protected $_label;

    /**
     * Apply filter to given query
     *
     * @param array $selection The filter selection
     * @param midcom_core_query $query The query object
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
        echo '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png" class="filter_action filter_apply" alt="' . $l10n->get("apply") . '" title="' . $l10n->get("apply") . '" />';
        echo '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png" class="filter_action filter_unset" alt="' . $l10n->get("unset") . '" title="' . $l10n->get("unset") . '" />';
    }
}
