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
     * @var org_openpsa_core_filter[]
     */
    private $_filters = [];

    /**
     * The queryfilter's identifier
     *
     * @var string
     */
    private $_identifier;

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
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.js');
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.css');
        foreach ($this->_filters as $filter) {
            $filter->add_head_elements();
            if ($selection = $this->_get_selection($filter->name)) {
                $filter->apply($selection, $query);
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
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.core');
        $filter_id = $this->_identifier . '_' . $filtername;
        $user = midcom::get()->auth->user->get_storage();

        if (   isset($_POST['unset_filter'])
            && $_POST['unset_filter'] == $filter_id) {
            if (   $user->get_parameter("org_openpsa_core_filter", $filter_id)
                && !$user->delete_parameter("org_openpsa_core_filter", $filter_id)) {
                $message_content = sprintf(
                    $l10n->get('the handed filter for %s could not be set as parameter'),
                    $l10n->get_string($filtername)
                );
                midcom::get()->uimessages->add($l10n->get('filter error'), $message_content, 'error');
            }
            return false;
        }
        if (isset($_POST[$filtername])) {
            $selection = (array) $_POST[$filtername];
            $filter_string = serialize($selection);
            if (!$user->set_parameter("org_openpsa_core_filter", $filter_id, $filter_string)) {
                midcom::get()->uimessages->add($l10n->get('filter error'), $l10n->get('the handed filter for %s could not be set as parameter'), 'error');
            }
            return $selection;
        }
        if (isset($_GET[$filtername])) {
            return (array) $_GET[$filtername];
        }
        if ($filter_string = $user->get_parameter("org_openpsa_core_filter", $filter_id)) {
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
        echo '<form id="org_openpsa_core_queryfilter" class="org_openpsa_queryfilter" action="' . $url . '" method="post" style="display:inline">';

        foreach ($this->_filters as $filter) {
            echo '<div class="org_openpsa_filter_widget" id="' . $this->_identifier . '_' . $filter->name . '">';
            $filter->render($url);
            echo "</div>\n";
        }
        echo "\n</form>\n";
    }
}
