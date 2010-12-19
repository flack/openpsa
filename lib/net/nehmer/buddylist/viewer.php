<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
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
    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, $args)
    {
        if ($this->_config->get('user'))
        {
            $this->_request_data['user'] = $_MIDCOM->auth->get_user('user:' . $this->_config->get('user'));
            if (!$this->_request_data['user'])
            {
                throw new midcom_error('No user found for this buddy list.');
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