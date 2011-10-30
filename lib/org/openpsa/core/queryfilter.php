<?php
/**
 * @package org.openpsa.core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class to handle query filters
 * Filters are saved to current user in the parameter field
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_queryfilter
{
    /**
     * Currently registered filters
     *
     * @var array
     */
    private $_filters = array();

    /**
     * The queryfilter's identifier
     *
     * @var string
     */
    private $_identifier;

    private $_selection = array();

    /**
     * Constructor
     *
     * @param string $identifier The QF's identifier
     */
    public function __construct($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Adds a filter to the queue
     *
     * @param org_openpsa_core_filter $filter The filter to add
     */
    public function add_filter(org_openpsa_core_filter $filter)
    {
        $this->_filters[] = $filter;
    }

    /**
     * Apply registered filters to query object
     *
     * @param midcom_core_query $query the query object
     */
    public function apply_filters(midcom_core_query $query)
    {
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.js');
        midcom::get('head')->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.css');
        foreach ($this->_filters as $filter)
        {
            $filter->add_head_elements();
            if ($selection = $this->_get_selection($filter->name))
            {
                $filter->apply($selection, $query);
                $this->_selection[$filter->name] = $selection;
            }
        }
    }

    /**
     * Queries multiple sources for filter selection information
     *
     * @param string $filtername The filter name to query
     * @return array The selected options, if any
     */
    private function _get_selection($filtername)
    {
        $i18n = midcom::get('i18n');
        $filter_id = $this->_identifier . '_' . $filtername;
        $user = midcom::get('auth')->user->get_storage();

        if (   isset($_POST['unset_filter'])
            && $_POST['unset_filter'] == $filtername . '_filter')
        {
            if (   $user->get_parameter("org_openpsa_core_filter", $filter_id)
                && !$user->delete_parameter("org_openpsa_core_filter", $filter_id))
            {
                $message_content = sprintf
                (
                    $i18n->get_string('the handed filter for %s could not be set as parameter', 'org.openpsa.core'),
                    $i18n->get_string($filtername, 'org.openpsa.core')
                );
                midcom::get('uimessages')->add($i18n->get_string('filter error', 'org.openpsa.core'), $message_content, 'error');
            }
        }
        else if (isset($_POST[$filtername]))
        {
            $selection = $_POST[$filtername];
            if (!is_array($selection))
            {
                $selection = array($selection);
            }

            $filter_string = serialize($selection);
            if (!$user->set_parameter("org_openpsa_core_filter", $filter_id, $filter_string))
            {
                midcom::get('uimessages')->add($i18n->get_string('filter error', 'org.openpsa.core'), $i18n->get_string('the handed filter for %s could not be set as parameter', 'org.openpsa.core'), 'error');
            }
            return $selection;
        }
        else if (isset($_GET[$filtername]))
        {
            $selection = $_GET[$filtername];
            if (!is_array($selection))
            {
                $selection = array($selection);
            }
            return $selection;
        }
        else if ($filter_string = $user->get_parameter("org_openpsa_core_filter", $filter_id))
        {
            return unserialize($filter_string);
        }
        return false;
    }

    /**
     * Renders the UI for the currently registered filters
     */
    public function render()
    {
        $url = midcom_connection::get_url('uri');
        if (!empty($this->_selection))
        {
            $url .= '?' . http_build_query($this->_selection);
        }
        foreach ($this->_filters as $filter)
        {
            echo '<div class="org_openpsa_filter_widget">';
            echo '<form id="' . $filter->name . '_filter" class="filter" action="' . $url . '" method="post" style="display:inline">';

            $filter->render();
            echo "</div>\n";
        }
    }
}
?>