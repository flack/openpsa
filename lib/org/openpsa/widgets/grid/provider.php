<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Manager for retrieving grid data by AJAX
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_grid_provider
{
    /**
     * The class responsible for getting and formatting rows
     *
     * @var org_openpsa_widgets_grid_provider_client
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

    /**
     * The grid we're working with
     *
     * @var org_openpsa_widgets_grid
     */
    private $_grid;

    /**
     * The midcom query object
     *
     * @var midcom_core_query
     */
    private $_query;

    public function __construct(org_openpsa_widgets_grid_provider_client $client)
    {
        $this->_client = $client;
    }

    public function set_grid(org_openpsa_widgets_grid &$grid)
    {
        $this->_grid =& $grid;
    }

    public function set_rows(array $rows)
    {
        $this->_rows = $rows;
        $this->_total_rows = count($this->_rows);
    }

    public function get_rows()
    {
        if (is_null($this->_rows))
        {
            $this->_get_rows();
        }
        return $this->_rows;
    }

    public function count_rows()
    {
        if (is_null($this->_total_rows))
        {
            $qb = $this->_prepare_query();
            $this->_total_rows = $qb->count();
        }
        return $this->_total_rows;
    }

    public function setup_grid()
    {
        if ($this->_get_grid_option('datatype', 'json') == 'local')
        {
            $this->render();
            $this->_grid->set_option('data', $this->_grid->get_identifier() . '_entries', false);
            if (null === $this->_get_grid_option('rowNum'))
            {
                $this->_grid->set_option('rowNum', $this->count_rows());
            }
        }
    }

    public function render()
    {
        if (is_null($this->_rows))
        {
            $this->_get_rows();
        }

        switch ($this->_get_grid_option('datatype', 'json'))
        {
            case 'json':
                $this->_render_json();
                break;
            case 'local':
                $this->_render_local();
                break;
            default:
                debug_add('Datatype ' . $this->_get_grid_option('datatype', 'json') . ' is not supported', MIDCOM_LOG_ERROR);
                throw new midcom_error('Unsupported datatype');
        }
    }

    private function _get_grid_option($key, $default = null)
    {
        if (empty($this->_grid))
        {
            return $default;
        }
        else
        {
            return $this->_grid->get_option($key);
        }
    }

    private function _render_local()
    {
        echo "var " . $this->_grid->get_identifier() . '_entries = ' .  json_encode($this->_rows) . ";\n";
    }

    private function _render_json()
    {
        if (is_null($this->_total_rows))
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
        $_MIDCOM->header('Content-type: application/json; charset=UTF-8');

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

    private function _prepare_query()
    {
        if (is_null($this->_query))
        {
            if ($this->_get_grid_option('datatype', 'json') == 'json')
            {
                $this->_parse_query($_GET);
            }

            $this->_query = $this->_client->get_qb($this->_sort_field, $this->_sort_direction);
        }
        return $this->_query;
    }

    private function _get_rows()
    {
        $qb = $this->_prepare_query();

        $this->_total_rows = $qb->count();
        $this->_rows = array();

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