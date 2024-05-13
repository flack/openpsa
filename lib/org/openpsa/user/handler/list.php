<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_list extends midcom_baseclasses_components_handler
implements client
{
    private provider $_provider;

    private ?midcom_db_group $_group = null;

    public function _on_initialize()
    {
        $this->_provider = new provider($this);
    }

    /**
     * Handler for listing users
     */
    public function _handler_list(array &$data, ?string $guid = null)
    {
        $auth = midcom::get()->auth;
        if (!$auth->can_user_do('org.openpsa.user:access', class: org_openpsa_user_interface::class)) {
            return new midcom_response_relocate($this->router->generate('user_view', ['guid' => $auth->user->guid]));
        }

        $data['provider_url'] = $this->router->generate('user_list_json');
        $grid_id = 'org_openpsa_user_grid';
        if ($guid !== null) {
            $grid_id = 'org_openpsa_members_grid';
            $this->_group = new midcom_db_group($guid);
            $data['group'] = $this->_group;
            $data['provider_url'] .= 'members/' . $guid . '/';
        }

        $data['grid'] = $this->_provider->get_grid($grid_id);

        $workflow = $this->get_workflow('datamanager');
        if (midcom::get()->auth->can_user_do('midgard:create', class: midcom_db_person::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('user_create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_GLYPHICON => 'user',
            ]));
        }

        if (midcom::get()->auth->can_user_do('midgard:create', class: midcom_db_group::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('group_create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create group'),
                MIDCOM_TOOLBAR_GLYPHICON => 'group',
            ]));
        }

        return $this->show('users-grid');
    }

    /**
     * Lists users in JSON format
     */
    public function _handler_json(array &$data, ?string $guid = null)
    {
        midcom::get()->skip_page_style = true;
        $data['provider'] = $this->_provider;
        if ($guid !== null) {
            $this->_group = new midcom_db_group($guid);
        }

        return $this->show('users-grid-json');
    }

    /**
     * Get querybuilder for JSON user list
     */
    public function get_qb(?string $field = null, string $direction = 'ASC', array $search = []) : midcom_core_query
    {
        $qb = midcom_db_person::new_collector();

        if ($this->_group) {
            $qb->get_doctrine()
                ->leftJoin('midgard_member', 'm', Join::WITH, 'm.uid = c.id')
                ->setParameter('gid', $this->_group->id);
            $qb->get_current_group()->add('m.gid = :gid');
        }

        foreach ($search as $search_field => $value) {
            if ($search_field == 'username') {
                midcom_core_account::add_username_constraint($qb, 'LIKE', $value . '%');
            } else {
                $qb->add_constraint($search_field, 'LIKE', $value . '%');
            }
        }

        if ($field !== null) {
            if ($field == 'username') {
                midcom_core_account::add_username_order($qb, $direction);
            } else {
                $qb->add_order($field, $direction);
            }
        }
        $qb->add_order('lastname');
        $qb->add_order('firstname');
        $qb->add_order('id');
        return $qb;
    }

    /**
     * Prepares user data for JSON display
     */
    public function get_row(midcom_core_dbaobject $user) : array
    {
        $link = $this->router->generate('user_view', ['guid' => $user->guid]);
        $lastname = trim($user->lastname) ?: $this->_l10n->get('person') . ' #' . $user->id;
        $account = new midcom_core_account($user);

        return [
            'id' => $user->id,
            'lastname' => "<a href='" . $link . "'>" . $lastname . "</a>",
            'index_lastname' => $user->lastname,
            'firstname' => "<a href='" . $link . "' >" . $user->firstname . "</a>",
            'index_firstname' => $user->firstname,
            'username' => $account->get_username(),
            'email' => $user->email
        ];
    }
}
