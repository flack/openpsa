<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for jqgrid controls
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_controls extends midcom_baseclasses_components_purecode
{
    private $grid_id;

    private $grouping = array();

    /**
     *
     * @var org_openpsa_core_queryfilter
     */
    private $filter;

    public function __construct($grid_id)
    {
        $this->grid_id = $grid_id;
    }

    public function set_grouping(array $choices)
    {
        $choices['clear'] = $this->_l10n->get('no grouping');
        $this->grouping = $choices;
    }

    public function set_filter(org_openpsa_core_queryfilter $filter)
    {
        $this->filter = $filter;
    }

    public function render()
    {
        echo $this->__toString();
    }

    public function __toString()
    {
        $ret = '';

        if (!empty($this->filter))
        {
            $ret .= $this->filter->render();
        }

        if (!empty($this->grouping))
        {
            $ret .= ' ' . midcom::get()->i18n->get_string('group by', 'org.openpsa.core') . ': ';
            $ret .= '<select id="chgrouping_' . $this->grid_id . '">';
            foreach ($this->grouping as $field => $label)
            {
                $ret .= '<option value="' . $field . '">' . $label . '</option';
            }
            $ret .= '</select>';
        }
        return $ret;
    }
}