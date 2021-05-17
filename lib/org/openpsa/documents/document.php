<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\blobs;

/**
 * DBA class for org_openpsa_document
 *
 * Implements parameter and attachment methods for DM compatibility
 *
 * @property integer $author
 * @property integer $topic
 * @property integer $nextVersion
 * @property string $title
 * @property string $abstract
 * @property string $keywords
 * @property integer $docStatus For status flags like: DRAFT, etc, could even be a bitmask stored as integer
	        	status seems to be a reserved word in some layer between DM -> DB
 * @property string $content plaintext representation of content, non-ML
 * @property integer $orgOpenpsaAccesstype Shortcut for various ACL scenarios
 * @property string $orgOpenpsaOwnerWg The "owner" workgroup of this object
 * @package org.openpsa.documents
 */
class org_openpsa_documents_document_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_document';

    public $autodelete_dependents = [
        self::class => 'nextVersion'
    ];

    const STATUS_DRAFT = 4000;
    const STATUS_FINAL = 4001;
    const STATUS_REVIEW = 4002;

    public function _on_loaded()
    {
        if ($this->title == "") {
            $this->title = "Document #{$this->id}";
        }

        if (!$this->docStatus) {
            $this->docStatus = self::STATUS_DRAFT;
        }
    }

    public function _on_creating() : bool
    {
        if (!$this->author) {
            $user = midcom::get()->auth->user->get_storage();
            $this->author = $user->id;
        }
        return true;
    }

    public function _on_created()
    {
        $this->_update_directory_timestamp();
    }

    public function _on_updated()
    {
        $this->_update_directory_timestamp();

        // Sync the object's ACL properties into MidCOM ACL system
        $sync = new org_openpsa_core_acl_synchronizer();
        $sync->write_acls($this, $this->orgOpenpsaOwnerWg, $this->orgOpenpsaAccesstype);
    }

    public function _on_deleted()
    {
        $this->_update_directory_timestamp();
    }

    private function _update_directory_timestamp()
    {
        if ($this->nextVersion != 0) {
            return;
        }
        $parent = $this->get_parent();
        if (   $parent
            && $parent->component == 'org.openpsa.documents') {
            midcom::get()->auth->request_sudo('org.openpsa.documents');

            $parent = new org_openpsa_documents_directory($parent);
            $parent->_use_rcs = false;
            $parent->update();

            midcom::get()->auth->drop_sudo();
        }
    }

    public function get_label() : string
    {
        return $this->title;
    }

    /**
     * Load the document's attachment
     */
    public function load_attachment() : ?midcom_db_attachment
    {
        if (!$this->guid) {
            // Non-persistent object will not have attachments
            return null;
        }

        $attachments = blobs::get_attachments($this, 'document');
        if (empty($attachments)) {
            return null;
        }

        if (count($attachments) > 1) {
            debug_add("Multiple attachments have been found for document #" . $this->id . ", returning only the first.", MIDCOM_LOG_INFO);
        }

        return reset($attachments);
    }

    public function backup_version() : bool
    {
        // Instantiate the backup object
        $backup = new org_openpsa_documents_document_dba();
        $properties = $this->get_properties();
        // Copy current properties
        foreach ($properties as $key) {
            if (!in_array($key, ['guid', 'id', 'metadata'])) {
                $backup->$key = $this->{$key};
            }
        }

        $backup->nextVersion = $this->id;
        if (!$backup->create()) {
            return false;
        }

        // Copy parameters
        if ($params = $this->list_parameters()) {
            foreach ($params as $domain => $array) {
                foreach ($array as $name => $value) {
                    $backup->set_parameter($domain, $name, $value);
                }
            }
        }

        // Find the attachments
        foreach ($this->list_attachments() as $original_attachment) {
            $backup_attachment = $backup->create_attachment($original_attachment->name, $original_attachment->title, $original_attachment->mimetype);

            $original_handle = $original_attachment->open('r');
            if (   !$backup_attachment
                || !$original_handle) {
                // Failed to copy the attachment, abort
                return $backup->delete();
            }

            // Copy the contents
            $backup_handle = $backup_attachment->open('w');

            stream_copy_to_stream($original_handle, $backup_handle);

            $original_attachment->close();

            // Copy attachment parameters
            if ($params = $original_attachment->list_parameters()) {
                foreach ($params as $domain => $array) {
                    foreach ($array as $name => $value) {
                        if ($name == 'identifier') {
                            $value = md5(time() . $backup_attachment->name);
                            $backup->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_document', $value . ":" . $backup_attachment->guid);
                        }
                        $backup_attachment->set_parameter($domain, $name, $value);
                    }
                }
            }
        }
        return true;
    }
}
