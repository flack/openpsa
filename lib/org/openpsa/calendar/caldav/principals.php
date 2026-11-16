<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;

/**
 * CalDAV principal backend: Maps principals/<username> to persons
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_caldav_principals extends AbstractBackend
{
    public const PREFIX = 'principals';

    /**
     * @var midcom_db_person[]
     */
    private array $persons = [];

    public function get_person(string $principal_uri) : ?midcom_db_person
    {
        if (!str_starts_with($principal_uri, self::PREFIX . '/')) {
            return null;
        }
        $username = substr($principal_uri, strlen(self::PREFIX) + 1);
        if ($username === '' || str_contains($username, '/')) {
            return null;
        }
        if (!array_key_exists($username, $this->persons)) {
            $qb = midcom_db_person::new_query_builder();
            midcom_core_account::add_username_constraint($qb, '=', $username);
            midcom::get()->auth->request_sudo('org.openpsa.calendar');
            $this->persons[$username] = $qb->get_result(0) ?: null;
            midcom::get()->auth->drop_sudo();
        }
        return $this->persons[$username];
    }

    public static function get_principal_uri(string $username) : string
    {
        return self::PREFIX . '/' . $username;
    }

    private function to_principal(midcom_db_person $person, string $username) : array
    {
        $principal = [
            'uri' => self::get_principal_uri($username),
            '{DAV:}displayname' => $person->rname ?: $username,
        ];
        if (!empty($person->email)) {
            // Sabre also uses this to populate calendar-user-address-set with mailto:
            $principal['{http://sabredav.org/ns}email-address'] = $person->email;
        }
        return $principal;
    }

    /**
     * @param midcom_db_person[] $persons
     */
    private function to_principals(array $persons) : array
    {
        $principals = [];
        foreach ($persons as $person) {
            $username = (new midcom_core_account($person))->get_username();
            if ($username !== '') {
                $this->persons[$username] = $person;
                $principals[] = $this->to_principal($person, $username);
            }
        }
        return $principals;
    }

    private function get_user_qb() : midcom_core_querybuilder
    {
        $qb = midcom_db_person::new_query_builder();
        midcom_core_account::add_username_constraint($qb, '<>', '');
        return $qb;
    }

    public function getPrincipalsByPrefix($prefixPath)
    {
        if (trim($prefixPath, '/') !== self::PREFIX) {
            return [];
        }
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $persons = $this->get_user_qb()->execute();
        midcom::get()->auth->drop_sudo();

        return $this->to_principals($persons);
    }

    public function getPrincipalByPath($path)
    {
        $path = trim($path, '/');
        if ($person = $this->get_person($path)) {
            return $this->to_principal($person, substr($path, strlen(self::PREFIX) + 1));
        }
        return null;
    }

    public function updatePrincipal($path, PropPatch $propPatch)
    {
        // Principal properties are read-only, unhandled properties result in 403
    }

    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        if (trim($prefixPath, '/') !== self::PREFIX) {
            return [];
        }
        $fields = [
            '{DAV:}displayname' => ['firstname', 'lastname'],
            '{http://sabredav.org/ns}email-address' => ['email'],
        ];
        $unsupported = array_diff_key($searchProperties, $fields);
        $searchProperties = array_intersect_key($searchProperties, $fields);
        if (   empty($searchProperties)
            || ($test === 'allof' && !empty($unsupported))) {
            // Unsupported properties can never match
            return [];
        }

        $qb = $this->get_user_qb();
        $qb->begin_group($test === 'anyof' ? 'OR' : 'AND');
        foreach ($searchProperties as $property => $value) {
            $qb->begin_group('OR');
            foreach ($fields[$property] as $field) {
                $qb->add_constraint($field, 'LIKE', '%' . $value . '%');
            }
            $qb->end_group();
        }
        $qb->end_group();

        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $persons = $qb->execute();
        midcom::get()->auth->drop_sudo();

        return array_column($this->to_principals($persons), 'uri');
    }

    public function getGroupMemberSet($principal)
    {
        return [];
    }

    public function getGroupMembership($principal)
    {
        return [];
    }

    public function setGroupMemberSet($principal, array $members)
    {
        throw new Forbidden('Group membership cannot be changed via CalDAV');
    }
}
