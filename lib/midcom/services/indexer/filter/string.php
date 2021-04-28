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
 */
class midcom_services_indexer_filter_string implements midcom_services_indexer_filter
{
    /**
     * The string we're working with
     *
     * @var string
     */
    private $value;

    /**
     * The name of the field that should be restricted.
     *
     * @var string
     */
    private $field = '';

    /**
     * Create a new string filter.
     */
    public function __construct(string $field, string $value)
    {
        $this->value = $value;
        $this->field = $field;
    }

    public function get_query_string() : string
    {
        return sprintf('%s:%s', $this->field, $this->value);
    }
}
