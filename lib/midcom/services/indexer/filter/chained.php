<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * This class provides a chained query filter
 *
 * @package midcom.services
 */
class midcom_services_indexer_filter_chained implements midcom_services_indexer_filter
{
    /**
     * @var midcom_services_indexer_filter[]
     */
    private $filters = [];

    public function add_filter(midcom_services_indexer_filter $filter)
    {
        $this->filters[] = $filter;
    }

    public function get_query_string() : string
    {
        $ret = [];
        foreach ($this->filters as $filter) {
            $ret[] = $filter->get_query_string();
        }
        return implode(' AND ', $ret);
    }

    public function count() : int
    {
        return count($this->filters);
    }
}
