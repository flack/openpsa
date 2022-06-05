<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\container\container;

/**
 * Handler addons
 *
 * @package org.openpsa.user
 */
trait org_openpsa_user_handler
{
    /**
     * Create account based on data from datamanager
     */
    public function create_account(midcom_db_person $person, container $formdata) : bool
    {
        if (empty($formdata['username'])) {
            return false;
        }
        $account_helper = new org_openpsa_user_accounthelper();
        $password = "";

        //take user password?
        if ((int) $formdata['password']['switch'] > 0) {
            $password = $formdata['password']['password'];
        }

        $stat = $account_helper->create_account(
            $person->guid,
            $formdata['username'],
            $person->email,
            $password,
            !empty($formdata['send_welcome_mail'])
        );
        if (!$stat) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $account_helper->errstr, 'error');
        }

        return $stat;
    }
}
