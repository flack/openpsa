<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for provider clients
 *
 * @package org.openpsa.widgets
 */
interface org_openpsa_widgets_grid_provider_client
{
    /**
     * Get the query builder for the resultset
     *
     * @param string $field Optional ordering field
     * @param string $direction Optional ordering direction
     * @param array $search Optional search filters
     * @return midcom_core_query QB or MC instance
     */
    public function get_qb($field = null, $direction = 'ASC', array $search = array());

    /**
     * Transfers a result object into a grid row
     *
     * @return array The row item
     */
    public function get_row(midcom_core_dbaobject $object);
}
