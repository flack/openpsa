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
     */
    public function get_attachment(string $name) : ?midcom_db_attachment
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return null;
        }

        // Locate attachment
        $qb = $this->get_attachment_qb();
        $qb->add_constraint('name', '=', $name);
        return $qb->execute()[0] ?? null;
    }

    /**
     * Delete an attachment by its name.
     * If multiple attachments match the name (should not happen in reality), the
     * first match will be deleted.
     */
    public function delete_attachment(string $name) : bool
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
     */
    public function create_attachment(string $name, string $title, string $mimetype) : ?midcom_db_attachment
    {
        if (!$this->id) {
            debug_add('Cannot create attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return null;
        }

        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:attachments')) {
            debug_add("Failed to set parameters, midgard:update or midgard:attachments on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return null;
        }

        $attachment = new midcom_db_attachment();
        $attachment->name = $name;
        $attachment->title = $title;
        $attachment->mimetype = $mimetype;
        $attachment->parentguid = $this->guid;

        if (!$attachment->create()) {
            debug_add("Could not create the attachment '{$name}' for " . get_class($this) . " {$this->guid}: "  . midcom_connection::get_error_string(),
            MIDCOM_LOG_INFO);
            return null;
        }

        return $attachment;
    }

    /**
     * Returns a prepared query builder that is already limited to the attachments of the given
     * object.
     */
    public function get_attachment_qb() : ?\midcom_core_querybuilder
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_WARN);
            return null;
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
    public function list_attachments() : array
    {
        if (!$this->id) {
            debug_add('Cannot retrieve attachments on a non-persistent object.', MIDCOM_LOG_INFO);
            return [];
        }

        return $this->get_attachment_qb()->execute();
    }
}
