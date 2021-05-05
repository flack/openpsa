<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_list extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private $_persons = [];

    public function _on_initialize()
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.user/jquery.midgard_admin_user.js');
    }

    private function _prepare_toolbar(array $data)
    {
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('user_create'),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create user'),
                MIDCOM_TOOLBAR_GLYPHICON => 'user-o',
                MIDCOM_TOOLBAR_ENABLED => $this->_config->get('allow_manage_accounts'),
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('group_list'),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('groups'),
                MIDCOM_TOOLBAR_GLYPHICON => 'users',
            ]
        ];
        $data['asgard_toolbar']->add_items($buttons);
    }

    public function _handler_list(Request $request, array &$data)
    {
        // See what fields we want to use in the search
        $data['search_fields'] = $this->_config->get_array('search_fields');
        $data['list_fields'] = $this->_config->get_array('list_fields');

        $this->_list_persons($request->query);

        // Used in select
        $data['groups_for_select'] = [];

        if (!empty($this->_persons)) {
            $this->list_groups_for_select(0, $data, 0);
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');
        }

        $this->add_breadcrumb($this->router->generate('user_list'), $data['view_title']);
        $this->_prepare_toolbar($data);
        return $this->get_response();
    }

    private function _list_persons(ParameterBag $query)
    {
        $qb = midcom_db_person::new_query_builder();
        $qb->add_order('lastname');
        $qb->add_order('firstname');

        if ($search = $query->get('midgard_admin_user_search')) {
            // Run the person-seeking QB
            $qb->begin_group('OR');
            foreach ($this->_request_data['search_fields'] as $field) {
                if ($field == 'username') {
                    midcom_core_account::add_username_constraint($qb, 'LIKE', "{$search}%");
                } else {
                    $qb->add_constraint($field, 'LIKE', "{$search}%");
                }
            }
            $qb->end_group();

            $this->_persons = $qb->execute();
        }
        // List all persons if there are less than N of them
        elseif ($qb->count_unchecked() < $this->_config->get('list_without_search')) {
            $this->_persons = $qb->execute();
        }
    }

    /**
     * Internal helper for showing the groups recursively
     */
    private function list_groups_for_select(int $id, array &$data, int $level)
    {
        $mc = midcom_db_group::new_collector('owner', $id);

        // Set the order
        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('official');
        $mc->add_order('name');

        // Get the results
        $groupdata = $mc->get_rows(['name', 'official', 'id', 'guid']);

        foreach ($groupdata as $group) {
            $group['title'] = ($group['official'] ?: $group['name']) ?: "#{$group['id']}";
            $group['level'] = $level;

            $data['groups_for_select'][] = $group;
            $this->list_groups_for_select($group['id'], $data, $level + 1);
        }
    }

    public function _show_list(string $handler_id, array &$data)
    {
        $data['persons'] = $this->_persons;
        midcom_show_style('midgard-admin-user-personlist-header');

        foreach ($data['persons'] as $person) {
            $data['person'] = $person;
            midcom_show_style('midgard-admin-user-personlist-item');
        }

        midcom_show_style('midgard-admin-user-personlist-footer');
    }

    public function _handler_batch(Request $request, string $action)
    {
        $relocate_url = $this->router->generate('user_list');
        if ($request->query->count() > 0) {
            $relocate_url .= '?' . $request->getQueryString();
        }
        if (empty($request->request->get('midgard_admin_user'))) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), $this->_l10n->get('empty selection'));
            return new midcom_response_relocate($relocate_url);
        }

        $method = '_batch_' . $action;

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('guid', 'IN', $request->request->get('midgard_admin_user'));
        $this->_persons = $qb->execute();
        foreach ($this->_persons as $person) {
            $this->$method($request, $person);
        }
        return new midcom_response_relocate($relocate_url);
    }

    private function _batch_groupadd(Request $request, midcom_db_person $person)
    {
        if ($request->request->has('midgard_admin_user_group')) {
            $member = new midcom_db_member();
            $member->uid = $person->id;
            $member->gid = $request->request->getInt('midgard_admin_user_group');
            if ($member->create()) {
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('user %s added to group'), $person->name));
            }
        }
    }

    private function _batch_removeaccount(Request $request, midcom_db_person $person)
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            return;
        }
        $account = new midcom_core_account($person);
        $person->set_parameter('midgard.admin.user', 'username', $account->get_username());
        if ($account->delete()) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('user account revoked for %s'), $person->name));
        }
    }

    /**
     * Internal helper for processing the batch change of passwords
     */
    private function _batch_passwords(Request $request, midcom_db_person $person)
    {
        // Set the mail commo parts
        $mail = new org_openpsa_mail();
        $mail->from = $this->_config->get('message_sender');
        $mail->encoding = 'UTF-8';

        try {
            $account = new midcom_core_account($person);
        } catch (midcom_error $e) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('failed to get the user with id %s'), $person->id), 'error');
            return;
        }

        // This shortcut is used in case of errors
        $person_edit_url = "<a href=\"" . $this->router->generate('user_edit', ['guid' => $person->guid]) . "\">{$person->name}</a>";

        // Cannot send the email if address is not specified
        if (!$person->email) {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('no email address defined for %s'), $person_edit_url), 'error');
            return;
        }

        // if account has no username, we don't need to set a password either
        if ($account->get_username() == '') {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('no username defined for %s'), $person_edit_url), 'error');
            return;
        }

        // Recipient
        $mail->to = $person->email;

        // Store the old password
        $person->set_parameter('midgard.admin.user', 'old_password', $account->get_password());

        // Get a new password
        $password = midgard_admin_user_plugin::generate_password(8);

        $mail->body = $request->request->get('body');
        $mail->subject = $request->request->get('subject');
        $now = time();
        $mail->parameters = [
            'PASSWORD' => $password,
            'FROM' => $this->_config->get('message_sender'),
            'LONGDATE' => $this->_l10n->get_formatter()->datetime($now, 'full'),
            'SHORTDATE' => $this->_l10n->get_formatter()->date($now),
            'TIME' => $this->_l10n->get_formatter()->time($now),
            'FIRSTNAME' => $person->firstname,
            'LASTNAME' => $person->lastname,
            'USERNAME' => $account->get_username(),
            'EMAIL' => $person->email,
        ];

        // Send the message
        if ($mail->send()) {
            // Set the password
            $account->set_password($password);

            if (!$account->save()) {
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('failed to update the password for %s'), $person_edit_url), 'error');
                return;
            }
            // Show UI message on success
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), $this->_l10n->get('passwords updated and mail sent'));
        } else {
            midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), "Failed to send the mail, SMTP returned error " . $mail->get_error_message(), 'error');
        }
    }

    /**
     * Batch process password form
     */
    public function _handler_password_email(array &$data)
    {
        // Set page title and default variables
        $data['view_title'] = $this->_l10n->get('batch generate passwords');
        $formatter = $this->_l10n->get_formatter();
        $now = time();
        $data['variables'] = [
            '__FIRSTNAME__' => $this->_l10n->get('firstname'),
            '__LASTNAME__' => $this->_l10n->get('lastname'),
            '__USERNAME__' => $this->_l10n->get('username'),
            '__EMAIL__' => $this->_l10n->get('email'),
            '__PASSWORD__' => $this->_l10n->get('password'),
            '__FROM__' => $this->_l10n->get('sender') . ' (' . $this->_config->get('message_sender') . ')',
            '__LONGDATE__' => sprintf($this->_l10n->get('long dateformat (%s)'), $formatter->datetime($now, 'full')),
            '__SHORTDATE__' => sprintf($this->_l10n->get('short dateformat (%s)'), $formatter->date($now)),
            '__TIME__' => sprintf($this->_l10n->get('current time (%s)'), $formatter->time($now)),
        ];

        $data['message_subject'] = $this->_l10n->get($this->_config->get('message_subject'));
        $data['message_body'] = $this->_l10n->get($this->_config->get('message_body'));
        $data['message_footer'] = $this->_config->get('message_footer');

        midcom::get()->skip_page_style = true;
        return $this->show('midgard-admin-user-password-email');
    }
}
