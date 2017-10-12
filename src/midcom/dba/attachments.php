<?php
/**
 * @package midcom.dba
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\dba;

use midcom_connection;
use midcom_db_attachment;
use midcom;

/**
 * midcom attachment support
 *
 * @package midcom.dba
 */
trait attachments
{
    /**
     * Retrieves an attachment by its name.
     * If multiple attachments match the name (should not happen in reality), the
     * first match will be returned.
     *
     * @param string $name The name of the attachment to look up.
     * @return midcom_db_attachment The attachment found, or false on failure.
     */
    public function get_attachment($name)
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return false;
        }

        // Locate attachment
        $qb = $this->get_attachment_qb();
        $qb->add_constraint('name', '=', $name);
        $result = $qb->execute();

        if (count($result) == 0) {
            return false;
        }

        return $result[0];
    }

    /**
     * Delete an attachment by its name.
     * If multiple attachments match the name (should not happen in reality), the
     * first match will be deleted.
     *
     * @param string $name The name of the attachment to delete.
     * @return bool Indicating success.
     */
    public function delete_attachment($name)
    {
        $attachment = $this->get_attachment($name);

        if (!$attachment) {
            debug_add("Tried to delete the attachment {$name} at the object " . get_class($this) . " {$this->guid}, but it did not exist. Failing silently.");
            return false;
        }

        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:attachments')) {
            debug_add("Failed to set parameters, midgard:update or midgard:attachments on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return false;
        }

        return $attachment->delete();
    }

    /**
     * Creates a new attachment at the current object and returns it for usage.
     *
     * @param string $name The name of the attachment.
     * @param string $title The title of the attachment.
     * @param string $mimetype The MIME-Type of the attachment.
     * @return midcom_db_attachment The created attachment or false on failure.
     */
    public function create_attachment($name, $title, $mimetype)
    {
        if (!$this->id) {
            debug_add('Cannot create attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return false;
        }

        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:attachments')) {
            debug_add("Failed to set parameters, midgard:update or midgard:attachments on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return false;
        }

        $attachment = new midcom_db_attachment();
        $attachment->name = $name;
        $attachment->title = $title;
        $attachment->mimetype = $mimetype;
        $attachment->parentguid = $this->guid;

        if (!$attachment->create()) {
            debug_add("Could not create the attachment '{$name}' for " . get_class($this) . " {$this->guid}: "  . midcom_connection::get_error_string(),
            MIDCOM_LOG_INFO);
            return false;
        }

        return $attachment;
    }

    /**
     * Returns a prepared query builder that is already limited to the attachments of the given
     * object.
     *
     * @return midcom_core_querybuilder The initialized instance of the query builder or false on failure.
     */
    public function get_attachment_qb()
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return false;
        }

        $qb = midcom::get()->dbfactory->new_query_builder('midcom_db_attachment');
        $qb->add_constraint('parentguid', '=', $this->guid);

        return $qb;
    }

    /**
     * Returns a complete list of attachments for the current object. If there are no
     * attachments, an empty array is returned.
     *
     * @return midcom_db_attachment[] A list of attachments
     */
    public function list_attachments()
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return [];
        }

        return $this->get_attachment_qb()->execute();
    }
}
