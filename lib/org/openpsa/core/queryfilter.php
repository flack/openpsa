<?php
/**
 * @package org.openpsa.core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Class to handle query filters
 * Filters are saved to current user in the parameter field
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_queryfilter
{
    /**
     * @var org_openpsa_core_filter[]
     */
    private array $_filters = [];

    private string $_identifier;

    /**
     * @param string $identifier The QF's identifier
     */
    public function __construct(string $identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Adds a filter to the queue
     */
    public function add_filter(org_openpsa_core_filter $filter)
    {
        $this->_filters[] = $filter;
    }

    /**
     * Apply registered filters to query object
     */
    public function apply_filters(midcom_core_query $query, Request $request)
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.js');
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.css');
        foreach ($this->_filters as $filter) {
            $filter->add_head_elements();
            if ($selection = $this->get_selection($filter->name, $request)) {
                $filter->apply($selection, $query);
            }
        }
    }

    /**
     * Queries multiple sources for filter selection information
     */
    private function get_selection(string $filtername, Request $request) : ?array
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.core');
        $filter_id = $this->_identifier . '_' . $filtername;
        $user = midcom::get()->auth->user->get_storage();

        if ($request->request->get('unset_filter') == $filter_id) {
            if (   $user->get_parameter("org_openpsa_core_filter", $filter_id)
                && !$user->delete_parameter("org_openpsa_core_filter", $filter_id)) {
                $message_content = sprintf(
                    $l10n->get('the handed filter for %s could not be set as parameter'),
                    $l10n->get($filtername)
                );
                midcom::get()->uimessages->add($l10n->get('filter error'), $message_content, 'error');
            }
            return null;
        }
        if ($request->request->has($filtername)) {
            $selection = $request->request->all($filtername);
            $filter_string = serialize($selection);
            if (!$user->set_parameter("org_openpsa_core_filter", $filter_id, $filter_string)) {
                midcom::get()->uimessages->add($l10n->get('filter error'), $l10n->get('the handed filter for %s could not be set as parameter'), 'error');
            }
            return $selection;
        }
        if ($request->query->has($filtername)) {
            return $request->query->all($filtername);
        }
        if ($filter_string = $user->get_parameter("org_openpsa_core_filter", $filter_id)) {
            return unserialize($filter_string);
        }
        return null;
    }

    /**
     * Renders the UI for the currently registered filters
     */
    public function render()
    {
        echo '<form id="org_openpsa_core_queryfilter" class="org_openpsa_queryfilter" action="" method="post" style="display:inline">';

        foreach ($this->_filters as $filter) {
            echo '<div class="org_openpsa_filter_widget" id="' . $this->_identifier . '_' . $filter->name . '">';
            $filter->render();
            echo "</div>\n";
        }
        echo "\n</form>\n";
    }
}
