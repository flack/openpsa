<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Comments component.
 *
 * This is a component geared for communities offering a way to add comments on arbitrary
 * pages. It is primarily geared for dl'ed usage. Its regular welcome URL method only
 * shows the configuration interface, commenting the comments topic is prohibited as well.
 *
 * The component stores the data in its own table, indexed by the object guid they are
 * bound to. There is no threading support, comments are ordered by creation date.
 *
 * Commenting is currently only allowed for registered users for security reasons.
 * The user's name and E-Mail will be stored along with the created information in the
 * Metadata in case that the user gets deleted.
 *
 * <b>Install instructions</b>
 *
 * Just create a topic with this component assigned to it. I recommend dropping it out of
 * your navigation, as the component will by dynamically_loaded always, and the topic
 * itself is only there for configuration purposes.
 *
 * In your component (or style), add a DL line like this wherever you want the comment
 * feature available:
 *
 * midcom::get()->dynamic_load('/$path_to_comments_topic/comment/$guid');
 *
 * $guid is the GUID of the object you're commenting.
 *
 * @todo Install instruction
 * @todo Approval
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_interface extends midcom_baseclasses_components_interface
{
    /**
     * The delete handler will drop all entries associated with any deleted object
     * so that our DB is clean.
     *
     * Uses SUDO to ensure privileges.
     */
    public function _on_watched_dba_delete($object)
    {
        $sudo = midcom::get()->auth->request_sudo($this->_component);

        $result = net_nehmer_comments_comment::list_by_objectguid($object->guid);

        foreach ($result as $comment) {
            $comment->delete();
        }

        if ($sudo) {
            midcom::get()->auth->drop_sudo();
        }
    }
}
