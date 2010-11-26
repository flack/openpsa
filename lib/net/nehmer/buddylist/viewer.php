<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: viewer.php 17359 2008-09-03 12:30:58Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Buddylist site interface class
 *
 * See the various handler classes for details.
 *
 * @package net.nehmer.buddylist
 */
class net_nehmer_buddylist_viewer extends midcom_baseclasses_components_request
{
    function _on_initialize()
    {
        // Generic and personal welcome pages
        $this->_request_switch['welcome'] = Array
        (
            'handler' => Array('net_nehmer_buddylist_handler_welcome', 'welcome'),
        );

        // Delete handler, only there for POST request data processing
        $this->_request_switch['delete'] = Array
        (
            'handler' => Array('net_nehmer_buddylist_handler_delete', 'delete'),
            'fixed_args' => 'delete',
        );

        $this->_request_switch['pending_list'] = Array
        (
            'handler' => Array('net_nehmer_buddylist_handler_pending', 'list'),
            'fixed_args' => Array('pending', 'list'),
        );
        $this->_request_switch['pending_process'] = Array
        (
            'handler' => Array('net_nehmer_buddylist_handler_pending', 'process'),
            'fixed_args' => Array('pending', 'process'),
        );

        $this->_request_switch['request'] = Array
        (
            'handler' => Array('net_nehmer_buddylist_handler_request', 'request'),
            'fixed_args' => Array('request'),
            'variable_args' => 1,
        );
    }

    /**
     * The handle callback populates the toolbars.
     */
    function _on_handle($handler, $args)
    {
        if ($this->_config->get('user'))
        {
            $this->_request_data['user'] = $_MIDCOM->auth->get_user('user:' . $this->_config->get('user'));
            if (!$this->_request_data['user'])
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'No user found for this buddy list.');
                // This will exit.
            }
        }
        else
        {
            $_MIDCOM->auth->require_valid_user();
            $this->_request_data['user'] = $_MIDCOM->auth->user;
        }

        return true;
    }
}

?>