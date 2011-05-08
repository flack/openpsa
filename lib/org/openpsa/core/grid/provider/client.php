<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for provider clients
 *
 * @package org.openpsa.core
 */
interface org_openpsa_core_grid_provider_client
{
    /**
     * Get the query builder for the resultset
     *
     * @param string $field Optional ordering field
     * @param string $direction Optional ordering direction
     * @return midcom_core_querybuilder QB instance
     */
    public function get_qb($field = null, $direction = 'ASC');

    /**
     * Transfers a result object into a grid row
     *
     * @return array The row item
     */
    public function get_row(midcom_core_dbaobject $object);
}
?>