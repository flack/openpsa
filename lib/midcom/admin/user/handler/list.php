<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_list extends midcom_baseclasses_components_handler
{
    private $_persons = array();

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.admin.user/jquery.midcom_admin_user.js');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data)
    {
        $buttons = array(
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create user'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_config->get('allow_manage_accounts'),
            ),
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('groups'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
            ),
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create group'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
            )
        );
        $data['asgard_toolbar']->add_items($buttons);
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        // See what fields we want to use in the search
        $data['search_fields'] = $this->_config->get('search_fields');
        $data['list_fields'] = $this->_config->get('list_fields');

        $this->_list_persons();

        // Used in many checks, keys are IDs, values objects
        $data['groups'] = array();

        // Used in select
        $data['groups_for_select'] = array();
        if (midcom::get()->auth->admin) {
            $data['groups_for_select'][] = array(
                'title' => 'Midgard Administrators',
                'level' => '0',
                'id' => 0,
            );
        }

        if (count($this->_persons) > 0) {
            $this->list_groups_for_select(0, $data, 0);
        }

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $data['view_title']);
        $this->_prepare_toolbar($data);
        return new midgard_admin_asgard_response($this, '_show_list');
    }

    private function _list_persons()
    {
        $qb = midcom_db_person::new_query_builder();
        $qb->add_order('lastname');
        $qb->add_order('firstname');

        if (isset($_REQUEST['midcom_admin_user_search'])) {
            // Run the person-seeking QB
            $qb->begin_group('OR');
            foreach ($this->_request_data['search_fields'] as $field) {
                if ($field == 'username') {
                    midcom_core_account::add_username_constraint($qb, 'LIKE', "{$_REQUEST['midcom_admin_user_search']}%");
                } else {
                    $qb->add_constraint($field, 'LIKE', "{$_REQUEST['midcom_admin_user_search']}%");
                }
            }
            $qb->end_group('OR');

            $this->_persons = $qb->execute();
        }
        // List all persons if there are less than N of them
        elseif ($qb->count_unchecked() < $this->_config->get('list_without_search')) {
            $this->_persons = $qb->execute();
        }
    }

    /**
     * Internal helper for showing the groups recursively
     *
     * @param int $id
     * @param array &$data
     * @param int $level
     */
    private function list_groups_for_select($id, &$data, $level)
    {
        $mc = midcom_db_group::new_collector('owner', (int) $id);

        // Set the order
        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('official');
        $mc->add_order('name');

        // Get the results
        $groupdata = $mc->get_rows(array('name', 'official', 'id', 'guid'));

        // Hide empty groups
        if (count($groupdata) === 0) {
            return;
        }

        $data['parent_id'] = $id;

        foreach ($groupdata as $group) {
            $group['title'] = $group['official'] ?: $group['name'];
            if (!$group['title']) {
                $group['title'] = "#{$group['id']}";
            }
            $group['level'] = $level;

            $data['groups_for_select'][] = $group;
            $level++;
            $this->list_groups_for_select($group['id'], $data, $level);
            $level--;
        }
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['persons'] = $this->_persons;
        midcom_show_style('midcom-admin-user-personlist-header');

        foreach ($data['persons'] as $person) {
            $data['person'] = $person;
            midcom_show_style('midcom-admin-user-personlist-item');
        }

        midcom_show_style('midcom-admin-user-personlist-footer');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_batch($handler_id, array $args, array &$data)
    {
        $relocate_url = '__mfa/asgard_midcom.admin.user/';
        if (!empty($_GET)) {
            $relocate_url .= '?' . http_build_query($_GET);
        }
        if (   empty($_POST['midcom_admin_user'])
            || !is_array($_POST['midcom_admin_user'])) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), $this->_l10n->get('empty selection'));
            return new midcom_response_relocate($relocate_url);
        }

        $method = '_batch_' . $args[0];
        if (!method_exists($this, $method)) {
            throw new midcom_error('Unknown action');
        }

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('guid', 'IN', $_POST['midcom_admin_user']);
        $this->_persons = $qb->execute();
        foreach ($this->_persons as $person) {
            $this->$method($person);
        }
        return new midcom_response_relocate($relocate_url);
    }

    private function _batch_groupadd(midcom_db_person $person)
    {
        if (isset($_POST['midcom_admin_user_group'])) {
            $member = new midcom_db_member();
            $member->uid = $person->id;
            $member->gid = (int) $_POST['midcom_admin_user_group'];
            if ($member->create()) {
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('user %s added to group'), $person->name));
            }
        }
    }

    private function _batch_removeaccount(midcom_db_person $person)
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            return;
        }
        $person->set_parameter('midcom.admin.user', 'username', $person->username);
        $account = new midcom_core_account($person);
        if ($account->delete()) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('user account revoked for %s'), $person->name));
        }
    }

    /**
     * Internal helper for processing the batch change of passwords
     */
    private function _batch_passwords(midcom_db_person $person)
    {
        // Set the mail commo parts
        $mail = new org_openpsa_mail();
        $mail->from = $this->_config->get('message_sender');
        $mail->encoding = 'UTF-8';

        // Get the context prefix
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        try {
            $account = new midcom_core_account($person);
        } catch (midcom_error $e) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('failed to get the user with id %s'), $person->id), 'error');
            return;
        }

        // This shortcut is used in case of errors
        $person_edit_url = "<a href=\"{$prefix}__mfa/asgard_midcom.admin.user/edit/{$person->guid}\">{$person->name}</a>";

        // Cannot send the email if address is not specified
        if (!$person->email) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('no email address defined for %s'), $person_edit_url), 'error');
            return;
        }

        // if account has no username, we don't need to set a password either
        if ($account->get_username() == '') {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('no username defined for %s'), $person_edit_url), 'error');
            return;
        }

        // Recipient
        $mail->to = $person->email;

        // Store the old password
        $person->set_parameter('midcom.admin.user', 'old_password', $account->get_password());

        // Get a new password
        $password = midcom_admin_user_plugin::generate_password(8);

        $mail->body = $_POST['body'];
        $mail->subject = $_POST['subject'];
        $now = time();
        $mail->parameters = array(
            'PASSWORD' => $password,
            'FROM' => $this->_config->get('message_sender'),
            'LONGDATE' => $this->_l10n->get_formatter()->datetime($now, 'full'),
            'SHORTDATE' => $this->_l10n->get_formatter()->date($now),
            'TIME' => $this->_l10n->get_formatter()->time($now),
            'FIRSTNAME' => $person->firstname,
            'LASTNAME' => $person->lastname,
            'USERNAME' => $account->get_username(),
            'EMAIL' => $person->email,
        );

        // Send the message
        if ($mail->send()) {
            // Set the password
            $account->set_password($password);

            if (!$account->save()) {
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('failed to update the password for %s'), $person_edit_url), 'error');
                return;
            }
            // Show UI message on success
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), $this->_l10n->get('passwords updated and mail sent'));
        } else {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), "Failed to send the mail, SMTP returned error " . $mail->get_error_message(), 'error');
        }
    }

    /**
     * Batch process password form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_password_email($handler_id, array $args, array &$data)
    {
        // Set page title and default variables
        $data['view_title'] = $this->_l10n->get('batch generate passwords');
        $formatter = $this->_l10n->get_formatter();
        $now = time();
        $data['variables'] = array(
            '__FIRSTNAME__' => $this->_l10n->get('firstname'),
            '__LASTNAME__' => $this->_l10n->get('lastname'),
            '__USERNAME__' => $this->_l10n->get('username'),
            '__EMAIL__' => $this->_l10n->get('email'),
            '__PASSWORD__' => $this->_l10n->get('password'),
            '__FROM__' => $this->_l10n->get('sender'),
            '__LONGDATE__' => sprintf($this->_l10n->get('long dateformat (%s)'), $formatter->datetime($now, 'full')),
            '__SHORTDATE__' => sprintf($this->_l10n->get('short dateformat (%s)'), $formatter->date($now)),
            '__TIME__' => sprintf($this->_l10n->get('current time (%s)'), $formatter->time($now)),
        );
    }

    /**
     * Show the batch password change form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_password_email($handler_id, array &$data)
    {
        $data['message_subject'] = $this->_l10n->get($this->_config->get('message_subject'));
        $data['message_body'] = $this->_l10n->get($this->_config->get('message_body'));

        midcom_show_style('midcom-admin-user-password-email');
    }
}
