<?php
/**
 * @package midcom.grid
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\grid\provider;

/**
 * Interface for provider clients
 *
 * @package midcom.grid
 */
interface client
{
    /**
     * Get the query builder for the resultset
     *
     * @param string $field Optional ordering field
     * @param string $direction Optional ordering direction
     * @param array $search Optional search filters
     * @return \midcom_core_query QB or MC instance
     */
    public function get_qb(string $field = null, string $direction = 'ASC', array $search = []) : \midcom_core_query;

    /**
     * Transfers a result object into a grid row
     *
     * @return array The row item
     */
    public function get_row(\midcom_core_dbaobject $object) : array;
}
