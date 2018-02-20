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
    protected $_person;

    /**
     * The account we're working on
     *
     * @var midcom_core_account
     */
    protected $_account;

    public $errstr;

    public function __construct(midcom_db_person $person = null)
    {
        if (null !== $person) {
            $this->_person = $person;
        }
        parent::__construct();
    }

    private function _get_account()
    {
        if ($this->_account === null) {
            $this->_account = new midcom_core_account($this->_person);
        }
        return $this->_account;
    }

    /**
     * can be called by various handlers
     *
     * @param string password: leave blank for auto generated
     */
    public function create_account($person_guid, $username, $usermail, $password = "", $send_welcome_mail = false)
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
            $this->errstr = "Unable to deliver welcome mail: no usermail adress given";
            return false;
        }

        // Check if we get the person
        $this->_person = new midcom_db_person($person_guid);
        $this->_person->require_do('midgard:update');

        //need to generate password?
        if (empty($password)) {
            $generated_password = true;
            $password = $this->generate_safe_password($this->_config->get("min_password_length"));
        } else {
            $generated_password = false;
        }

        $account = $this->_get_account();

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
            $mail->from = $this->_config->get('welcome_mail_from_address');
            $mail->subject = $this->_config->get('welcome_mail_title');
            $mail->body = $this->_config->get('welcome_mail_body');

            // Make replacements to body
            $mail->parameters = [
                "USERNAME" => $username,
                "PASSWORD" => $password,
                "SITE_URL" => midcom::get()->config->get('midcom_site_url')
            ];

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

        if (!empty($this->errstr)) {
            throw new midcom_error('Could not create account: ' . $this->errstr);
        }

        return true;
    }

    /**
     * Sets username and password for person
     *
     * @param string $username Contains username
     * @param string $new_password Contains the new password to set
     */
    public function set_account($username, $new_password)
    {
        $account = $this->_get_account();
        if (!empty($new_password)) {
            //check if the new encrypted password was already used
            if (    $this->check_password_reuse($new_password)
                && $this->check_password_strength($new_password)) {
                    $this->_save_old_password();
                    $account->set_password($new_password);
                } else {
                    $this->errstr = "password strength too low";
                    return false;
                }
        }

        $account->set_username($username);

        // probably username not unique
        if (!$account->save()) {
            $this->errstr = "Failed to save account, reason: " . midcom_connection::get_error_string();
            return false;
        }

        if (!empty($new_password)) {
            // add timestamp of password-change
            $this->_person->set_parameter("org_openpsa_user_password", "last_change", time());
        }
        // sets privilege
        midcom::get()->auth->request_sudo($this->_component);
        $this->_person->set_privilege('midgard:owner', "user:" . $this->_person->guid);
        midcom::get()->auth->drop_sudo();

        return true;
    }

    /**
     * Returns an auto generated password of variable length
     *
     * @param int $length The number of chars the password will contain
     * @return string The generated password
     */
    public static function generate_password($length = 0)
    {
        // We should do this by listening to /dev/urandom
        // Safety
        if ($length == 0) {
            $length = 8;
        }
        // Valid characters for default password (PONDER: make configurable ?)
        $passwdchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.,-*!:+=()/&%$<>?#@';
        $first_last_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        //make sure password doesn't begin or end in punctuation character
        $password = midcom_helper_misc::random_string(1, $first_last_chars);
        $password .= midcom_helper_misc::random_string($length - 2, $passwdchars);
        $password .= midcom_helper_misc::random_string(1, $first_last_chars);
        return $password;
    }

    /**
     * Returns an auto generated password which will pass the persons check_password_strength test
     *
     * @param int $length The number of chars the password will contain
     * @return string The generated password
     */
    public function generate_safe_password($length = 0)
    {
        do {
            $password = self::generate_password($length);
        } while (!$this->check_password_strength($password));
        return $password;
    }

    /**
     * Function to check if passed password was already used
     *
     * @param string $password Password to check
     * @return bool returns if password was already used - true indicates passed password wasn't used
     */
    function check_password_reuse($password, $show_ui_message = true)
    {
        // check current password
        if (midcom_connection::verify_password($password, $this->_get_account()->get_password())) {
            if($show_ui_message){
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password is the same as the current one'), 'error');
            }
            return false;
        }

        // get last passwords
        $old_passwords = $this->_get_old_passwords();

        // check last passwords
        foreach ($old_passwords as $old) {
            if (midcom_connection::verify_password($password, $old)) {
                if($show_ui_message){
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
    protected function _save_old_password()
    {
        $max_old_passwords = $this->_config->get('max_old_passwords');
        if ($max_old_passwords < 1) {
            return;
        }
        $old_passwords_array = $this->_get_old_passwords();
        array_unshift($old_passwords_array, $this->_get_account()->get_password());

        if (count($old_passwords_array) > $max_old_passwords) {
            array_pop($old_passwords_array);
        }

        $new_passwords_string = serialize($old_passwords_array);

        $this->_person->set_parameter("org_openpsa_user_password", "old_passwords", $new_passwords_string);
    }

    /**
     * Function get old passwords
     *
     * @return array - Array with old passwords - empty if there aren't any old passwords
     */
    protected function _get_old_passwords()
    {
        $old_passwords_string = $this->_person->get_parameter("org_openpsa_user_password", "old_passwords");
        if (!empty($old_passwords_string)) {
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
     *
     * @param string $password Contains password to check
     */
    function check_password_strength($password, $show_ui_message = true)
    {
        $password_length = strlen($password);

        // score for length & repetition
        $pattern_length = 4;
        $score_char = 4;
        $score = $password_length * $score_char;
        for ($count = 1; $count <= $pattern_length; $count++) {
            $score += strlen($this->_check_repetition($count, $password)) - $password_length;
        }

        $max = $this->_config->get('min_password_length');

        if ($password_length < $max) {
            if($show_ui_message){
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password too short'), 'error');
            }
            return false;
        }

        //check $password with rules
        $rules = $this->_config->get('password_score_rules');
        foreach ($rules as $rule) {
            if (preg_match($rule['match'], $password) > 0) {
                $score += $rule['score'];
            }
        }

        if ($score < $this->_config->get('min_password_score')) {
            if($show_ui_message){
                midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get('password weak'), 'error');
            }
            return false;
        }
        return true;
    }

    /**
     * Function to check repetition for given password
     *
     * @param integer $plen length to check for repetitions inside the password
     * @param string $password contains password to check
     * @return string - string without repetitions
     */
    protected function _check_repetition($plen, $password)
    {
        $result = "";
        for ($i = 0; $i < strlen($password); $i++) {
            $repeated = true;
            for ($j = 0; $j < $plen && ($j + $i + $plen) < strlen($password); $j++) {
                if (   (substr($password, $j + $i, 1) == substr($password, $j + $i + $plen, 1))
                    && $repeated) {
                        $repeated = true;
                    } else {
                        $repeated = false;
                    }
            }
            if ($j < $plen) {
                $repeated = false;
            }
            if ($repeated) {
                $i += $plen - 1;
            } else {
                $result .= substr($password, $i, 1);
            }
        }
        return $result;
    }

    /**
     * Function to check password age for this user (age is taken from config)
     *
     * @return boolean - true indicates password is ok - false password is to old
     */
    function check_password_age()
    {
        $max_age_days = $this->_config->get('password_max_age_days');
        if ($max_age_days == 0) {
            return true;
        }
        $max_timeframe = time() - ($max_age_days * 24 * 60 * 60);
        $last_change = $this->_person->get_parameter("org_openpsa_user_password", "last_change");

        if (empty($last_change)) {
            return false;
        }

        return ($max_timeframe < $last_change);
    }

    /**
     * Function to disable account for time period given in config
     *
     * @return boolean - indicates success
     */
    public function disable_account()
    {
        $account = $this->_get_account();

        $timeframe_minutes = $this->_config->get('password_block_timeframe_min');

        if ($timeframe_minutes == 0) {
            return false;
        }
        $release_time = time() + ($timeframe_minutes * 60);
        $args = [
            'guid' => $this->_person->guid,
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
        $this->_person->set_parameter("org_openpsa_user_blocked_account", "account_password", $account->get_password());
        $account->set_password('', false);
        return $account->save();
    }

    /**
     * Function to delete account
     *
     * @return boolean indicates success
     */
    public function delete_account()
    {
        return $this->_get_account()->delete();
    }

    /**
     * Permanently disable an user account
     *
     * @return boolean - indicates success
     */
    public function close_account()
    {
        $account = $this->_get_account();

        if (!$account->get_password()) {
            // the account is already blocked, so skip the rest
            return true;
        }

        $this->_person->set_parameter("org_openpsa_user_blocked_account", "account_password", $account->get_password());
        $account->set_password('', false);
        return $account->save();
    }

    /**
     * Reopen a blocked account.
     *
     * This will fail if someone set a new password on the account while it was blocked
     */
    public function reopen_account()
    {
        $account = new midcom_core_account($this->_person);
        if ($account->get_password()) {
            $this->_person->set_parameter('org_openpsa_user_blocked_account', 'account_password', "");
            $msg = 'Account for person #' . $this->_person->id . ' does have a password already';
            throw new midcom_error($msg);
        }

        $account->set_password($this->_person->get_parameter('org_openpsa_user_blocked_account', 'account_password'), false);
        if (!$account->save()) {
            throw new midcom_error('Failed to save account: ' . midcom_connection::get_error_string());
        }
        $this->_person->delete_parameter('org_openpsa_user_blocked_account', 'account_password');
    }

    /**
     * Determine if an account is blocked
     */
    public function is_blocked()
    {
        $block_param = $this->_person->get_parameter("org_openpsa_user_blocked_account", "account_password");

        return (!empty($block_param));
    }

    public static function get_person_by_formdata($data)
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

            if (sizeof($results) != 1) {
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
    public function check_login_attempts($component = null)
    {
        $stat = true;
        if (is_null($component)) {
            $component = "org.openpsa.user";
        }

        //max-attempts allowed & timeframe
        $max_attempts = midcom_baseclasses_components_configuration::get($component, 'config')->get('max_password_attempts');
        $timeframe = midcom_baseclasses_components_configuration::get($component, 'config')->get('password_block_timeframe_min');

        if (   $max_attempts == 0
            || $timeframe == 0) {
                return $stat;
            }

            midcom::get()->auth->request_sudo('org.openpsa.user');

            if ($attempts = $this->_person->get_parameter("org_openpsa_user_password", "attempts")) {
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
             * If the maximum number of attemps is reached and the oldest attempt
             * on the stack is within our defined timeframe, we block the account
             */
            if (   count($attempts) >= $max_attempts
                && $attempts[$max_attempts - 1] >= (time() - ($timeframe * 60))) {
                    $this->disable_account();
                    $stat = false;
                }

                $attempts = serialize($attempts);
                $this->_person->set_parameter("org_openpsa_user_password", "attempts", $attempts);
                midcom::get()->auth->drop_sudo();
                return $stat;
    }
}
