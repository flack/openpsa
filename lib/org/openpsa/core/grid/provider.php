<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Manager for retrieving grid data by AJAX
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_grid_provider
{
    /**
     * The class responsible for getting and formatting rows
     *
     * @var org_openpsa_core_grid_provider_client
     */
    private $_client;

    /**
     * The rows to show
     *
     * @var array
     */
    private $_rows;

    /**
     * The total number of rows
     *
     * @var int
     */
    private $_total_rows;

    /**
     * How many items should be shown per page
     *
     * @var int
     */
    private $_results_per_page = 20;

    /**
     * The current offset
     *
     * @var int
     */
    private $_offset;

    /**
     * The field for sorting
     *
     * @var string
     */
    private $_sort_field;

    /**
     * The direction for sorting (ASC or DESC)
     *
     * @var string
     */
    private $_sort_direction;

    public function __construct(org_openpsa_core_grid_provider_client $client)
    {
        $this->_client = $client;
    }

    public function set_rows(array $rows)
    {
        $this->_rows = $rows;
    }

    public function render()
    {
        if (is_null($this->_rows))
        {
            $this->_get_rows();
        }
        else
        {
            $this->_total_rows = count($this->_rows);
        }
        $response = array
        (
            'total' => ceil($this->_total_rows / $this->_results_per_page),
            'page' => ($this->_offset / $this->_results_per_page) + 1,
            'records' => $this->_total_rows,
            'rows' => $this->_rows
        );
        $_MIDCOM->cache->content->content_type('application/json');
        $_MIDCOM->header('Content-type: application/json; charset=UFT-8');

        echo json_encode($response);
    }

    private function _parse_query(array $query)
    {
        if (!empty($query['rows']))
        {
            $this->_results_per_page = (int) $query['rows'];
            if (!empty($query['page']))
            {
                $this->_offset = ($this->_results_per_page * ($query['page'] - 1));
            }
        }
        if (!empty($query['sidx']))
        {
            $this->_sort_field = $query['sidx'];
            $this->_sort_direction = strtoupper($query['sord']);
        }
    }

    private function _get_rows()
    {
        $this->_parse_query($_GET);
        $this->_rows = array();

        $qb = $this->_client->get_qb($this->_sort_field, $this->_sort_direction);

        $this->_total_rows = $qb->count();

        if (!empty($this->_results_per_page))
        {
            $qb->set_limit($this->_results_per_page);
            if (!empty($this->_offset))
            {
                $qb->set_offset($this->_offset);
            }
        }
        if ($qb instanceof midcom_core_querybuilder)
        {
            $items = $qb->execute();
        }
        else if ($qb instanceof midcom_core_collector)
        {
            $items = $qb->get_objects();
        }
        else
        {
            throw new midcom_error('Unsupported query class ' . get_class($qb));
        }
        foreach ($items as $item)
        {
            $this->_rows[] = $this->_client->get_row($item);
        }
    }
}
?>