<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: navigation.php 20857 2009-03-05 19:45:48Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management NAP interface class
 *
 * @package net.nehmer.account
 */

class net_nehmer_account_navigation extends midcom_baseclasses_components_navigation
{
    /**
     * Simple constructor, calls base class.
     */
    function __construct()
    {
        parent::__construct();
    }

    function get_leaves()
    {
        $leaves = Array();

        if ($_MIDCOM->auth->user == null)
        {
            if ($this->_config->get('allow_register'))
            {
                $leaves[NET_NEHMER_ACCOUNT_LEAFID_REGISTER] = array
                (
                    MIDCOM_NAV_URL => "register/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('account registration'),
                );
            }
            if ($this->_config->get('show_lost_password_in_navi'))
            {
                $leaves[NET_NEHMER_ACCOUNT_LEAFID_LOSTPASSWORD] = array
                (
                    MIDCOM_NAV_URL => "lostpassword/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('lost password'),
                );
            }
        }
        else
        {
            if ($this->_config->get('allow_change_password'))
            {
                $leaves[NET_NEHMER_ACCOUNT_LEAFID_PASSWORDCHANGE] = array
                (
                    MIDCOM_NAV_URL => "password/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('change password'),
                );
            }
        }

        // Pending registrations
        if (   $_MIDCOM->auth->admin
            && $this->_config->get('require_activation'))
        {
            // Get the count of persons waiting for approval
            $qb = midcom_db_person::new_query_builder();
            $qb->add_constraint('parameter.domain', '=', 'net.nehmer.account');
            $qb->add_constraint('parameter.name', '=', 'require_approval');
            $qb->add_constraint('parameter.value', '=', 'require_approval');
            
            $count = $qb->count();
            
            $leaves[NET_NEHMER_ACCOUNT_LEAFID_PENDING] = array
            (
                MIDCOM_NAV_URL => "pending/",
                MIDCOM_NAV_NAME => $this->_l10n->get('pending approvals') . " ({$count})",
            );
        }
        
        return $leaves;
    }

    /*
    function get_node()
    {
        return array
        (
            MIDCOM_NAV_URL => '',
            MIDCOM_NAV_NAME => $this->_topic->extra,
            MIDCOM_NAV_NOENTRY => $hidden,
        );
    }
     */

}

?>