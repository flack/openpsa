<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * DBA class for org_openpsa_document
 *
 * Implements parameter and attachment methods for DM compatibility
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_document_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_document';

    const STATUS_DRAFT = 4000;
    const STATUS_FINAL = 4001;
    const STATUS_REVIEW = 4002;

    const OBTYPE_DOCUMENT = 3000;

    function get_parent_guid_uncached()
    {
        // FIXME: Midgard Core should do this
        if (   isset($this->nextversion)
            && $this->nextversion != 0)
        {
            $parent = new org_openpsa_documents_document_dba($this->nextversion);
        }
        else
        {
            $parent = new org_openpsa_documents_directory($this->topic);
        }
        return $parent->guid;
    }

    public function _on_loaded()
    {
        if ($this->title == "")
        {
            $this->title = "Document #{$this->id}";
        }

        if (!$this->docStatus)
        {
            $this->docStatus = org_openpsa_documents_document_dba::STATUS_DRAFT;
        }
    }

    public function _on_creating()
    {
        $this->orgOpenpsaObtype = self::OBTYPE_DOCUMENT;
        if (!$this->author)
        {
            $user = midcom::get('auth')->user->get_storage();
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
        if ($this->nextVersion != 0)
        {
            return;
        }
        $parent = $this->get_parent();
        if (   $parent
            && $parent->component == 'org.openpsa.documents')
        {
            midcom::get('auth')->request_sudo('org.openpsa.documents');

            $parent = new org_openpsa_documents_directory($parent);
            $parent->_use_rcs = false;
            $parent->_use_activitystream = false;
            $parent->update();

            midcom::get('auth')->drop_sudo();
        }
    }

    public function get_class()
    {
        if (   !midcom::get('auth')->user
            || empty($this->guid))
        {
            return '';
        }
        $person = midcom::get('auth')->user->get_storage();
        $lastvisited = $person->get_parameter('org.openpsa.documents_visited', $this->guid);

        if (   $lastvisited
            && $lastvisited > $this->metadata->revised)
        {
            return 'visited';
        }
        return 'new';
    }

    /**
     * Helper function to load the document's attachment
     *
     * @return midcom_db_attachment The attachment object
     */
    public function load_attachment()
    {
        if (!$this->guid)
        {
            // Non-persistent object will not have attachments
            return null;
        }

        $raw_list = $this->get_parameter('midcom.helper.datamanager2.type.blobs', "guids_document");
        if (!$raw_list)
        {
            return null;
        }

        $attachment = array();

        $items = explode(',', $raw_list);

        if (sizeof($items) > 1)
        {
            debug_add("Multiple attachments have been found for document #" . $this->id . ", returning only the first.", MIDCOM_LOG_INFO);
        }

        foreach ($items as $item)
        {
            $info = explode(':', $item);
            if (   !is_array($info)
                || sizeof($info) < 2)
            {
                // Broken item
                debug_add("Attachment entry '{$item}' is broken!", MIDCOM_LOG_ERROR);
                continue;
            }
            $identifier = $info[0];
            $guid = $info[1];

            try
            {
                $attachment = new midcom_db_attachment($guid);
            }
            catch (midcom_error $e)
            {
                debug_add("Failed to load the attachment {$guid} from disk, aborting.", MIDCOM_LOG_INFO);
                debug_add('Last Midgard error was: ' . $e->getMessage(), MIDCOM_LOG_INFO);
                continue;
            }

            return $attachment;
        }
    }

    /**
     * Helper function that tries to generate a human-readable file type by
     * doing some educated guessing based on mimetypes
     *
     * @param string $mimetype The mimetype as reported by PHP
     * @return string The localized file type
     */
    public static function get_file_type($mimetype)
    {
        if (!preg_match('/\//', $mimetype))
        {
            return $mimetype;
        }

        //first, try if there is a direct translation
        if ($mimetype != midcom::get('i18n')->get_string($mimetype, 'org.openpsa.documents'))
        {
            return (midcom::get('i18n')->get_string($mimetype, 'org.openpsa.documents'));
        }

        //if nothing is found, do some heuristics
        $parts = explode('/', $mimetype);
        $type = $parts[0];
        $subtype = $parts[1];

        switch ($type)
        {
            case 'image':
                $subtype = strtoupper($subtype);
                break;
            case 'text':
                $type = 'document';
                break;
            case 'application':
                $type = 'document';

                if (preg_match('/^vnd\.oasis\.opendocument/', $subtype))
                {
                    $type = str_replace('vnd.oasis.opendocument.', '', $subtype);
                    $subtype = 'OpenDocument';
                }
                else if (preg_match('/^vnd\.ms/', $subtype))
                {
                    $subtype = ucfirst(str_replace('vnd.ms-', '', $subtype));
                }

                $subtype = preg_replace('/^vnd\./', '', $subtype);
                $subtype = preg_replace('/^x-/', '', $subtype);

                break;
            default:
                break;
        }

        /*
         * if nothing matched so far and the subtype is alphanumeric, uppercase it on the theory
         * that it's probably a file extension
         */
        if (   $parts[1] == $subtype
            && preg_match('/^[a-z0-9]+$/', $subtype))
        {
            $subtype = strtoupper($subtype);
        }

        return sprintf(midcom::get('i18n')->get_string('%s ' . $type, 'org.openpsa.documents'), $subtype);
    }

    function backup_version()
    {
        // Instantiate the backup object
        $backup = new org_openpsa_documents_document_dba();
        $properties = $this->get_properties();
        // Copy current properties
        foreach ($properties as $key)
        {
            if (   $key != 'guid'
                && $key != 'id'
                && $key != 'metadata')
            {
                $backup->$key = $this->{$key};
            }
        }

        $backup->nextVersion = $this->id;
        $stat = $backup->create();

        if (!$stat)
        {
            return $stat;
        }
        $backup = new org_openpsa_documents_document_dba($backup->id);

        // Copy parameters
        $params = $this->list_parameters();

        if ($params)
        {
            foreach ($params as $domain => $array)
            {
                foreach ($array as $name => $value)
                {
                    if ($name == 'identifier')
                    {
                        $value = $identifier = md5(time() . $backup_attachment->name);
                    }
                    $backup->set_parameter($domain, $name, $value);
                }
            }
        }

        // Find the attachment
        $attachments = $this->list_attachments();

        if (!$attachments)
        {
            return $stat;
        }

        foreach ($attachments as $original_attachment)
        {
            $backup_attachment = $backup->create_attachment($original_attachment->name, $original_attachment->title, $original_attachment->mimetype);

            $original_handle = $original_attachment->open('r');
            if (   !$backup_attachment
                || !$original_handle)
            {
                // Failed to copy the attachment, abort
                return $backup->delete();
            }

            // Copy the contents
            $backup_handle = $backup_attachment->open('w');

            while (!feof($original_handle))
            {
                fwrite($backup_handle, fread($original_handle, 4096), 4096);
            }
            fclose($original_handle);

            // Copy attachment parameters
            $params = $original_attachment->list_parameters();

            if ($params)
            {
                foreach ($params as $domain => $array)
                {
                    foreach ($array as $name => $value)
                    {
                        if ($name == 'identifier')
                        {
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
?>