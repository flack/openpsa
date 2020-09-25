<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package org.openpsa.user
 */

/**
 * Helper class for creating a new account for an existing person
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_accounthelper extends midcom_baseclasses_components_purecode
{
    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    protected $person;

    /**
     * The account we're working on
     *
     * @var midcom_core_account
     */
    private $account;

    public $errstr;

    public function __construct(midcom_db_person $person = null)
    {
        if (null !== $person) {
            $this->person = $person;
        }
        parent::__construct();
    }

    protected function get_account() : midcom_core_account
    {
        if ($this->account === null) {
            $this->account = new midcom_core_account($this->person);
        }
        return $this->account;
    }

    /**
     * can be called by various handlers
     *
     * @param string $password password: leave blank for auto generated
     */
    public function create_account(string $person_guid, string $username, string $usermail, string $password = "", bool $send_welcome_mail = false) : bool
    {
        $this->errstr = ""; // start fresh

        // quick validation
        if (empty($person_guid)) {
            $this->errstr = "Unable to identify user: no guid given";
            return false;
        }

        if (empty($username)) {
            $this->errstr = "Unable to create account: no username given";
            return false;
        }

        if ($send_welcome_mail && empty($usermail)) {
            $this->errstr = "Unable to deliver welcome mail: no usermail address given";
            return false;
        }

        // Check if we get the person
        $this->person = new midcom_db_person($person_guid);
        $this->person->require_do('midgard:update');

        //need to generate password?
        if (empty($password)) {
            $generated_password = true;
            $password = $this->generate_safe_password($this->_config->get("min_password_length"));
        } else {
            $generated_password = false;
        }

        $account = $this->get_account();

        //an account already existing?
        if ($account->get_password()) {
            $this->errstr = "Creating new account for existing account is not possible";
            return false;
        }

        //try creating
        if (!$this->set_account($username, $password)) {
            $this->errstr = "Could not set account, reason: " . midcom_connection::get_error_string();
            return false;
        }

        //send welcome mail?
        if ($send_welcome_mail) {
            $mail = new org_openpsa_mail();
            $mail->to = $usermail;

            // Make replacements to body
            $mail->parameters = [
                "USERNAME" => $username,
                "PASSWORD" => $password,
            ];

            $this->prepare_mail($mail);

            if (!$mail->send()) {
                $this->errstr = "Unable to deliver welcome mail: " . $mail->get_error_message();
                $this->delete_account();
                return false;
            }
        } elseif ($generated_password) {
            /*
             * no welcome mail was sent:
             * if the password was auto generated show it in an ui message
             */
            midcom::get()->uimessages->add(
                $this->_l10n->get('org.openpsa.user'),
                sprintf($this->_l10n->get("account_creation_success"), $username, $password), 'ok');
        }

        return true;
    }

    /**
     * Prepare the welcome mail for the user.
     *
     * The essential data (recipient, username, password) is already filled in
     * at this point. You can override this function in subclasses if you want
     * to customize the mail further
     *
     * @param org_openpsa_mail $mail
     */
    protected function prepare_mail(org_openpsa_mail $mail)
    {
        $mail->from = $this->_config->get('welcome_mail_from_address');
        $mail->subject = $this->_config->get('welcome_mail_title');
        $mail->body = $this->_config->get('welcome_mail_body');
        $mail->parameters["SITE_URL"] = midcom::get()->config->get('midcom_site_url');
    }

    /**
     * Sets username and password for person
     *
     * @param string $new_password Contains the new password to set
     */
    public function set_account(string $username, $new_password) : bool
    {
        $account = $this->get_account();
        if (!empty($new_password)) {
            //check if the new encrypted password was already used
            if (   !$this->check_password_reuse($new_password, true)
                || !$this->check_password_strength($new_password, true)) {
                $this->errstr = "password strength too low";
                return false;
            }
            $this->save_old_password();
            $account->set_password($new_password);
        }

        $account->set_username($username);

        // probably username not unique
        if (!$account->save()) {
            $this->errstr = "Failed to save account, reason: " . midcom_connection::get_error_string();
            return false;
        }

        if (!empty($new_password)) {
            // add timestamp of password-change
            $this->person->set_parameter("org_openpsa_user_password", "last_change", time());
        }
        // sets privilege
        midcom::get()->auth->request_sudo($this->_component);
        $this->person->set_privilege('midgard:owner', "user:" . $this->person->guid);
        midcom::get()->auth->drop_sudo();

        return true;
    }

    /**
     * Returns an auto generated password which will pass the persons check_password_strength test
     */
    public function generate_safe_password(int $length = 0) : string
    {
        do {
            $password = midgard_admin_user_plugin::generate_password($length);
        } while (!$this->check_password_strength($password));
        return $password;
    }

    /**
     * Function to check if passed password was already used
     *
     * @return bool returns true if password wasn't used already
     */
    public function check_password_reuse(string $password, bool $show_ui_message = false) : bool
    {
        // check current password
        if (midcom_connection::verify_password($password, $this->get_account()->get_password())) {
            if ($show_ui_message) {
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password is the same as the current one'), 'error');
            }
            return false;
        }

        // get last passwords
        $old_passwords = $this->get_old_passwords();

        // check last passwords
        foreach ($old_passwords as $old) {
            if (midcom_connection::verify_password($password, $old)) {
                if ($show_ui_message) {
                    midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password was already used'), 'error');
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Function to add current password to parameter old passwords - does not update()
     */
    private function save_old_password()
    {
        $max_old_passwords = $this->_config->get('max_old_passwords');
        if ($max_old_passwords < 1) {
            return;
        }
        $old_passwords_array = $this->get_old_passwords();
        array_unshift($old_passwords_array, $this->get_account()->get_password());

        if (count($old_passwords_array) > $max_old_passwords) {
            array_pop($old_passwords_array);
        }

        $new_passwords_string = serialize($old_passwords_array);

        $this->person->set_parameter("org_openpsa_user_password", "old_passwords", $new_passwords_string);
    }

    /**
     * Function get old passwords
     *
     * @return array - Array with old passwords - empty if there aren't any old passwords
     */
    private function get_old_passwords() : array
    {
        if ($old_passwords_string = $this->person->get_parameter("org_openpsa_user_password", "old_passwords")) {
            $old_passwords_array = unserialize($old_passwords_string);
            $count = count($old_passwords_array);
            $max = (int) $this->_config->get('max_old_passwords');
            if ($count > $max) {
                $old_passwords_array = array_slice($old_passwords_array, 0, $max);
            }
        } else {
            $old_passwords_array = [];
        }
        return $old_passwords_array;
    }

    /**
     * Function to check strength of passed password
     */
    public function check_password_strength(string $password, bool $show_ui_message = false) : bool
    {
        $password_length = mb_strlen($password);

        if ($password_length < $this->_config->get('min_password_length')) {
            if ($show_ui_message){
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password too short'), 'error');
            }
            return false;
        }

        // score for length & repetition
        $score = $this->count_unique_characters($password) * 4;

        //check $password with rules
        $rules = $this->_config->get('password_score_rules');
        foreach ($rules as $rule) {
            if (preg_match($rule['match'], $password) > 0) {
                $score += $rule['score'];
            }
        }

        if ($score < $this->_config->get('min_password_score')) {
            if ($show_ui_message){
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password weak'), 'error');
            }
            return false;
        }
        return true;
    }

    private function count_unique_characters(string $password) : int
    {
        // Split into individual (multibyte) characters, flip to filter out duplicates, and then count
        return count(array_flip(preg_split('//u', $password, null, PREG_SPLIT_NO_EMPTY)));
    }

    /**
     * Function to check password age for this user (age is taken from config)
     *
     * @return boolean - true indicates password is ok - false password is to old
     */
    public function check_password_age() : bool
    {
        $max_age_days = $this->_config->get('password_max_age_days');
        if ($max_age_days == 0) {
            return true;
        }

        if ($last_change = $this->person->get_parameter("org_openpsa_user_password", "last_change")) {
            $max_timeframe = time() - ($max_age_days * 24 * 60 * 60);
            return $max_timeframe < $last_change;
        }
        return false;
    }

    /**
     * Function to disable account for time period given in config
     *
     * @return boolean - indicates success
     */
    public function disable_account() : bool
    {
        $account = $this->get_account();

        $timeframe_minutes = $this->_config->get('password_block_timeframe_min');

        if ($timeframe_minutes == 0) {
            return false;
        }
        $release_time = time() + ($timeframe_minutes * 60);
        $args = [
            'guid' => $this->person->guid,
            'parameter_name' => 'org_openpsa_user_blocked_account',
            'password' => 'account_password',
        ];

        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('argumentsstore', '=', serialize($args));
        $qb->add_constraint('status', '=', midcom_services_at_entry_dba::SCHEDULED);
        if ($entry = $qb->get_result(0)) {
            //the account is already blocked, so we just extend the block's duration
            $entry->start = $release_time;
            return $entry->update();
        }

        if (!midcom_services_at_interface::register($release_time, 'org.openpsa.user', 'reopen_account', $args)) {
            throw new midcom_error("Failed to register interface for re_open the user account, last Midgard error was: " . midcom_connection::get_error_string());
        }
        $this->person->set_parameter("org_openpsa_user_blocked_account", "account_password", $account->get_password());
        $account->set_password('', false);
        return $account->save();
    }

    /**
     * Function to delete account
     *
     * @return boolean indicates success
     */
    public function delete_account() : bool
    {
        return $this->get_account()->delete();
    }

    /**
     * Permanently disable an user account
     *
     * @return boolean - indicates success
     */
    public function close_account() : bool
    {
        $account = $this->get_account();

        if (!$account->get_password()) {
            // the account is already blocked, so skip the rest
            return true;
        }

        $this->person->set_parameter("org_openpsa_user_blocked_account", "account_password", $account->get_password());
        $account->set_password('', false);
        return $account->save();
    }

    /**
     * Reopen a blocked account.
     */
    public function reopen_account()
    {
        $account = new midcom_core_account($this->person);
        if ($account->get_password()) {
            debug_add('Account for person #' . $this->person->id . ' does have a password already');
        } else {
            $account->set_password($this->person->get_parameter('org_openpsa_user_blocked_account', 'account_password'), false);
            if (!$account->save()) {
                throw new midcom_error('Failed to save account: ' . midcom_connection::get_error_string());
            }
        }

        $this->person->delete_parameter('org_openpsa_user_blocked_account', 'account_password');
    }

    /**
     * Determine if an account is blocked
     */
    public function is_blocked() : bool
    {
        return !empty($this->person->get_parameter("org_openpsa_user_blocked_account", "account_password"));
    }

    public static function get_person_by_formdata(array $data)
    {
        if (   empty($data['username'])
            || empty($data['password'])) {
            return false;
        }

        midcom::get()->auth->request_sudo('org.openpsa.user');
        $qb = midcom_db_person::new_query_builder();
        midcom_core_account::add_username_constraint($qb, '=', $data['username']);
        $results = $qb->execute();
        midcom::get()->auth->drop_sudo();

        if (count($results) != 1) {
            return false;
        }
        return $results[0];
    }

    /**
     * Record failed login attempts and disable account is necessary
     *
     * @param string $component the component we take the config values from
     * @return boolean True if further login attempts are allowed, false otherwise
     */
    public function check_login_attempts(string $component = null) : bool
    {
        $stat = true;
        $component = $component ?: "org.openpsa.user";

        //max-attempts allowed & timeframe
        $max_attempts = midcom_baseclasses_components_configuration::get($component, 'config')->get('max_password_attempts');
        $timeframe = midcom_baseclasses_components_configuration::get($component, 'config')->get('password_block_timeframe_min');

        if (   $max_attempts == 0
            || $timeframe == 0) {
            return $stat;
        }

        midcom::get()->auth->request_sudo('org.openpsa.user');

        if ($attempts = $this->person->get_parameter("org_openpsa_user_password", "attempts")) {
            $attempts = unserialize($attempts);
            if (is_array($attempts)) {
                $attempts = array_slice($attempts, 0, ($max_attempts - 1));
            }
        }
        if (!is_array($attempts)) {
            $attempts = [];
        }
        array_unshift($attempts, time());

        /*
         * If the maximum number of attempts is reached and the oldest attempt
         * on the stack is within our defined timeframe, we block the account
         */
        if (   count($attempts) >= $max_attempts
            && $attempts[$max_attempts - 1] >= (time() - ($timeframe * 60))) {
            $this->disable_account();
            $stat = false;
        }

        $attempts = serialize($attempts);
        $this->person->set_parameter("org_openpsa_user_password", "attempts", $attempts);
        midcom::get()->auth->drop_sudo();
        return $stat;
    }
}
