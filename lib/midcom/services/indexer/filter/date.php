<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class provides a date query filter
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
class midcom_services_indexer_filter_date extends midcom_services_indexer_filter
{
    /**
     * Start timestamp, may be 0
     *
     * @var int
     */
    private $_start;

    /**
     * End timestamp, may be 0
     *
     * @var int
     */
    private $_end;

    /**
     * Create a new date filter.
     *
     * Only one of the filter bounds may be 0, indicating no limit in that
     * direction.
     *
     * @param string $field The name of the field that should be filtered.
     * @param int $start Start of filter range (or 0 for no start filter)
     * @param int $end End of filter range (or 0 for no end filter)
     */
    public function __construct($field, $start, $end)
    {
        parent::__construct($field);

        if ($start == 0 && $end == 0) {
            throw new midcom_error('Either start or end must not be 0.');
        }

        $this->_start = $start;
        $this->_end = $end;
    }

    public function get_query_string()
    {
        $format = "Y-m-d\TH:i:s\Z";
        return sprintf("%s:[%s TO %s]",
            $this->get_field(),
            gmdate($format, $this->_start - 100000),
            gmdate($format, ($this->_end == 0) ? time() : $this->_end));
    }
}
