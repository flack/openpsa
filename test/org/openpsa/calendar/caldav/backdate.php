<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\caldav;

use midcom_db_person;
use midgard\portable\storage\connection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * Sync tokens are second-based and never the current second. Instead of waiting for
 * the clock, tests move existing changes into the past.
 *
 * @package openpsa.test
 */
trait backdate
{
    /**
     * Sets the revision timestamp of all of the person's events (including ones they left)
     * and all their eventmember rows to some seconds ago
     */
    private function backdate(midcom_db_person $person, int $seconds = 10)
    {
        $em = connection::get_em();
        $db = $em->getConnection();
        if (!$ids = $db->fetchFirstColumn('SELECT eid FROM eventmember WHERE uid = ?', [$person->id])) {
            return;
        }
        $params = [gmdate('Y-m-d H:i:s', time() - $seconds), $ids];
        $types = [ParameterType::STRING, ArrayParameterType::INTEGER];
        $db->executeStatement('UPDATE event SET metadata_revised = ? WHERE id IN (?)', $params, $types);
        $db->executeStatement('UPDATE eventmember SET metadata_revised = ? WHERE eid IN (?)', $params, $types);
        // Loaded entities still have the old timestamps
        $em->clear();
    }
}
