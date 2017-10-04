<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * This class provides a string query filter
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
class midcom_services_indexer_filter_string extends midcom_services_indexer_filter
{
    /**
     * The string we're working with
     *
     * @var string
     */
    private $value;

    /**
     * Create a new date filter.
     *
     * Only one of the filter bounds may be 0, indicating a no limit in that
     * direction.
     *
     * @param string $field The name of the field that should be filtered.
     * @param string $value The string we're looking for
     */
    public function __construct($field, $value)
    {
        parent::__construct($field);

        $this->value = $value;
    }

    public function get_query_string()
    {
        return sprintf('%s:%s', $this->get_field(), $this->value);
    }
}
