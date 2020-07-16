<?php
/**
 * @package org.openpsa.documents
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * elFinder driver
 *
 * @package org.openpsa.documents
 */
class elFinderVolumeOpenpsa extends elFinderVolumeDriver
{
    public function __construct()
    {
        // elfinder tmp detection doesn't work on OS X
        $this->tmp = midcom::get()->config->get('midcom_tempdir');
    }

    /**
     * Save uploaded file.
     * On success return array with new file stat and with removed file hash (if existed file was replaced)
     *
     * Copied from parent and slightly modified to support attachment versioning
     *
     * @param  Resource $fp      file pointer
     * @param  string   $dst     destination folder hash
     * @param  string   $name     file name
     * @param  string   $tmpname file tmp name - required to detect mime type
     * @return array|false
     * @author Dmitry (dio) Levashov
     **/
    public function upload($fp, $dst, $name, $tmpname, $hashes = [])
    {
        if ($this->commandDisabled('upload')) {
            return $this->setError(elFinder::ERROR_PERM_DENIED);
        }

        if (($dir = $this->dir($dst)) == false) {
            return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#' . $dst);
        }

        if (empty($dir['write'])) {
            return $this->setError(elFinder::ERROR_PERM_DENIED);
        }

        if (!$this->nameAccepted($name, false)) {
            return $this->setError(elFinder::ERROR_INVALID_NAME);
        }

        $mimeByName = '';
        if ($this->mimeDetect === 'internal') {
            $mime = $this->mimetype($tmpname, $name);
        } else {
            $mime = $this->mimetype($tmpname, $name);
            $mimeByName = $this->mimetype($name, true);
            if ($mime === 'unknown') {
                $mime = $mimeByName;
            }
        }

        if (!$this->allowPutMime($mime) || ($mimeByName && !$this->allowPutMime($mimeByName))) {
            return $this->setError(elFinder::ERROR_UPLOAD_FILE_MIME, '(' . $mime . ')');
        }

        $tmpsize = (int)sprintf('%u', filesize($tmpname));
        if ($this->uploadMaxSize > 0 && $tmpsize > $this->uploadMaxSize) {
            return $this->setError(elFinder::ERROR_UPLOAD_FILE_SIZE);
        }

        $dstpath = $this->decode($dst);
        if (isset($hashes[$name])) {
            $test = $this->decode($hashes[$name]);
            $file = $this->stat($test);
        } else {
            $test = $this->joinPathCE($dstpath, $name);
            $file = $this->isNameExists($test);
        }

        $this->clearcache();

        if ($file && $file['name'] === $name) { // file exists and check filename for item ID based filesystem
            if ($this->uploadOverwrite) {
                if (!$file['write']) {
                    return $this->setError(elFinder::ERROR_PERM_DENIED);
                } elseif ($file['mime'] == 'directory') {
                    return $this->setError(elFinder::ERROR_NOT_REPLACE, $name);
                }
                $document = new org_openpsa_documents_document_dba($test);
                $document->backup_version();
                $attachments = org_openpsa_helpers::get_dm2_attachments($document, 'document');
                foreach ($attachments as $att) {
                    if (!$att->delete()) {
                        return false;
                    }
                }

                if (!$this->create_attachment($document, $name, $mime, $fp)) {
                    return false;
                }
                //make sure metadata.revised changes
                $document->update();

                return $this->stat($document->guid);
            } else {
                $name = $this->uniqueName($dstpath, $name, '-', false);
            }
        }

        $stat = [
            'mime'   => $mime,
            'width'  => 0,
            'height' => 0,
            'size'   => $tmpsize];

        if (str_starts_with($mime, 'image') && ($s = getimagesize($tmpname))) {
            $stat['width'] = $s[0];
            $stat['height'] = $s[1];
        }

        if (($path = $this->saveCE($fp, $dstpath, $name, $stat)) == false) {
            return false;
        }

        $stat = $this->stat($path);
        // Try get URL
        if (empty($stat['url']) && ($url = $this->getContentUrl($stat['hash']))) {
            $stat['url'] = $url;
        }

        return $stat;
    }

    private function get_by_path($path)
    {
        try {
            return org_openpsa_documents_document_dba::get_cached($path);
        } catch (midcom_error $e) {
            $e->log();
            try {
                return org_openpsa_documents_directory::get_cached($path);
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        return false;
    }

    /**
     * Return parent directory path
     *
     * @param  string  $path  file path
     * @return string
     */
    protected function _dirname($path)
    {
        $object = $this->get_by_path($path);
        if ($object === false) {
            return '';
        }
        return $object->get_parent()->guid;
    }

    /**
     * Return file name
     *
     * @param  string  $path  file path
     * @return string
     */
    protected function _basename($path)
    {
        $object = $this->get_by_path($path);
        if ($object === false) {
            return '';
        }
        return $object->get_label();
    }

    /**
     * Join dir name and file name and return full path.
     * Some drivers (db) use int as path - so we give to concat path to driver itself
     *
     * @param  string  $dir   dir path
     * @param  string  $name  file name
     * @return string
     */
    protected function _joinPath($dir, $name)
    {
        $mc = org_openpsa_documents_document_dba::new_collector('title', $name);
        $mc->add_constraint('topic.guid', '=', $dir);
        $keys = $mc->list_keys();
        if ($keys) {
            return key($keys);
        }
        $mc = org_openpsa_documents_directory::new_collector('extra', $name);
        $mc->add_constraint('up.guid', '=', $dir);
        $keys = $mc->list_keys();
        if ($keys) {
            return key($keys);
        }
        return -1;
    }

    /**
     * Return normalized path
     *
     * @param  string  $path  file path
     * @return string
     */
    protected function _normpath($path)
    {
        return $path;
    }

    /**
     * Return file path related to root dir
     *
     * @param  string  $path  file path
     * @return string
     */
    protected function _relpath($path)
    {
        return $path;
    }

    /**
     * Convert path related to root dir into real path
     *
     * @param  string  $path  rel file path
     * @return string
     */
    protected function _abspath($path)
    {
        return $path;
    }

    /**
     * Return fake path started from root dir.
     * Required to show path on client side.
     *
     * @param  string  $path  file path
     * @return string
     */
    protected function _path($path)
    {
        $object = $this->get_by_path($path);
        if ($object === false) {
            return '';
        }
        $output = [$object->get_label()];

        $parent = $object->get_parent();
        while (   $parent
               && $parent->component == 'org.openpsa.documents') {
            $output[] = $parent->extra;
            $parent = $parent->get_parent();
        }
        $output[] = $this->rootName;
        return implode($this->separator, $output);
    }

    /**
     * Return true if $path is children of $parent
     *
     * @param  string  $path    path to check
     * @param  string  $parent  parent path
     * @return bool
     */
    protected function _inpath($path, $parent)
    {
        if ($path === $parent) {
            return true;
        }

        $object = midcom::get()->dbfactory->get_object_by_guid($path);
        try {
            $parentdir = org_openpsa_documents_directory::get_cached($parent);
        } catch (midcom_error $e) {
            $e->log();
            return false;
        }

        $qb = org_openpsa_documents_directory::new_query_builder();
        $qb->add_constraint('up', 'INTREE', $parentdir->id);
        if ($object instanceof org_openpsa_documents_document_dba) {
            $qb->add_constraint('id', '=', $object->topic);
        } else {
            $qb->add_constraint('id', '=', $object->id);
        }
        return $qb->count() > 0;
    }

    /**
     * Return stat for given path.
     * Stat contains following fields:
     * - (int)    size    file size in b. required
     * - (int)    ts      file modification time in unix time. required
     * - (string) mime    mimetype. required for folders, others - optionally
     * - (bool)   read    read permissions. required
     * - (bool)   write   write permissions. required
     * - (bool)   locked  is object locked. optionally
     * - (bool)   hidden  is object hidden. optionally
     * - (string) alias   for symlinks - link target path relative to root path. optionally
     * - (string) target  for symlinks - link target path. optionally
     *
     * If file does not exists - returns empty array or false.
     *
     * @param  string  $path    file path
     * @return array|false
     */
    protected function _stat($path)
    {
        if (!mgd_is_guid($path)) {
            return false;
        }
        $object = midcom::get()->dbfactory->get_object_by_guid($path);

        $data = [
            'ts' => $object->metadata->revised,
            'read' => true,
            'write' => $object->can_do('midgard:update'),
            'locked' => $object->metadata->is_locked(),
            'mime' => ''
        ];
        $creator = org_openpsa_widgets_contact::get($object->metadata->creator);
        $data['owner'] = $creator->show_inline();

        if ($object instanceof org_openpsa_documents_document_dba) {
            $owner = $object->orgOpenpsaOwnerWg;

            $attachments = org_openpsa_helpers::get_dm2_attachments($object, 'document');
            if ($attachments) {
                $att = current($attachments);
                $data['mime'] = $att->mimetype;
                if ($stat = $att->stat()) {
                    $data['size'] = $stat['size'];
                }
            }
        } else {
            $owner = $object->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg');

            $qb = org_openpsa_documents_directory::new_query_builder();
            $qb->add_constraint('up', '=', $object->id);
            $qb->add_constraint('component', '=', 'org.openpsa.documents');
            $qb->set_limit(1);
            $data['dirs'] = $qb->count();
            $data['mime'] = 'directory';
        }

        if (   $owner
            && $group = midcom::get()->auth->get_assignee($owner)) {
            $data['group'] = $group->name;
        }

        if (   $this->root !== $path
            && $parent = $object->get_parent()) {
            $data['phash'] = $this->encode($parent->guid);
        }
        return $data;
    }

    /**
     * Return true if path is dir and has at least one child directory
     *
     * @param  string  $path  dir path
     * @return bool
     */
    protected function _subdirs($path)
    {
        $topic = org_openpsa_documents_directory::get_cached($path);
        $qb = org_openpsa_documents_directory::new_query_builder();
        $qb->add_constraint('up', '=', $topic->id);
        $qb->add_constraint('component', '=', 'org.openpsa.documents');
        $qb->set_limit(1);
        return $qb->count() > 0;
    }

    /**
     * Return object width and height
     * Ususaly used for images, but can be realize for video etc...
     *
     * @param  string  $path  file path
     * @param  string  $mime  file mime type
     * @return string
     */
    protected function _dimensions($path, $mime)
    {
        throw new midcom_error('_dimensions not implemented');
    }

    /**
     * Return files list in directory
     *
     * @param  string  $path  dir path
     * @return array
     */
    protected function _scandir($path)
    {
        $topic = org_openpsa_documents_directory::get_cached($path);
        $mc = org_openpsa_documents_document_dba::new_collector('topic', $topic->id);
        $mc->add_constraint('nextVersion', '=', 0);
        $files = array_keys($mc->list_keys());
        $mc = org_openpsa_documents_directory::new_collector('up', $topic->id);
        return array_merge($files, array_keys($mc->list_keys()));
    }

    /**
     * Open file and return file pointer
     *
     * @param  string $path file path
     * @param  string $mode open mode
     * @return resource|false
     */
    protected function _fopen($path, $mode="rb")
    {
        $document = org_openpsa_documents_document_dba::get_cached($path);
        $attachments = org_openpsa_helpers::get_dm2_attachments($document, 'document');
        if ($attachments) {
            $att = current($attachments);
            return $att->open($mode);
        }
        return false;
    }

    /**
     * Close opened file
     *
     * @param  resource  $fp    file pointer
     * @param  string    $path  file path
     * @return bool
     */
    protected function _fclose($fp, $path='')
    {
        fclose($fp);
        return true;
    }

    /**
     * Create dir and return created dir path or false on failed
     *
     * @param  string  $path  parent dir path
     * @param string  $name  new directory name
     * @return string|bool
     */
    protected function _mkdir($path, $name)
    {
        $parent = org_openpsa_documents_directory::get_cached($path);
        $dir = new org_openpsa_documents_directory;
        $dir->extra = $name;
        $dir->component = 'org.openpsa.documents';
        $dir->up = $parent->id;
        if (!$dir->create()) {
            return false;
        }
        if ($groups = org_openpsa_helpers_list::workgroups()) {
            $dir->set_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg', key($groups));
        }
        $access_types = org_openpsa_core_acl::get_options();
        $dir->set_parameter('org.openpsa.core', 'orgOpenpsaAccesstype', key($access_types));

        return $dir->guid;
    }

    private function create_document($parentguid, $title)
    {
        $dir = org_openpsa_documents_directory::get_cached($parentguid);
        $document = new org_openpsa_documents_document_dba;
        $document->topic = $dir->id;
        $document->title = $title;
        $document->author = midcom_connection::get_user();
        $document->docStatus = org_openpsa_documents_document_dba::STATUS_DRAFT;
        $document->orgOpenpsaOwnerWg = $dir->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg');
        $document->orgOpenpsaAccesstype = $dir->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype');

        if ($document->create()) {
            return $document;
        }
        return false;
    }

    /**
     * Create file and return its path or false on failed
     *
     * @param  string  $path  parent dir path
     * @param string  $name  new file name
     * @return string|bool
     */
    protected function _mkfile($path, $name)
    {
        if ($document = $this->create_document($path, $name)) {
            return $document->guid;
        }
        return false;
    }

    /**
     * Create symlink
     *
     * @param  string  $source     file to link to
     * @param  string  $targetDir  folder to create link in
     * @param  string  $name       symlink name
     * @return bool
     */
    protected function _symlink($source, $targetDir, $name)
    {
        return false;
    }

    /**
     * Copy file into another file (only inside one volume)
     *
     * @param  string  $source     source file path
     * @param  string  $targetDir  target dir path
     * @param  string  $name       file name
     * @return bool
     */
    protected function _copy($source, $targetDir, $name)
    {
        $target = org_openpsa_documents_directory::get_cached($targetDir);
        $source = midcom::get()->dbfactory->get_object_by_guid($source);
        $copy = new midcom_helper_reflector_copy;
        $copy->target = $target;

        $new_object = $copy->execute($source);
        if ($new_object === false) {
            debug_print_r('Copying failed with the following errors', $copy->errors, MIDCOM_LOG_ERROR);
            return false;
        }

        return $new_object->guid;
    }

    /**
     * Move file into another parent dir.
     * Return new file path or false.
     *
     * @param  string  $source     source file path
     * @param  string  $targetDir  target dir path
     * @param  string  $name       file name
     * @return string|bool
     */
    protected function _move($source, $targetDir, $name)
    {
        $target = org_openpsa_documents_directory::get_cached($targetDir);
        if ($object = $this->get_by_path($source)) {
            if ($object instanceof org_openpsa_documents_document_dba) {
                $object->topic = $target->id;
                $object->title = $name;
            } else {
                $object->up = $target->id;
                $object->extra = $name;
            }
            if ($object->update()) {
                midcom::get()->cache->invalidate($target->guid);
                return $source;
            }
        }
        return false;
    }

    /**
     * Remove file
     *
     * @param  string  $path  file path
     * @return bool
     */
    protected function _unlink($path)
    {
        try {
            $doc = new org_openpsa_documents_document_dba($path);
            return $doc->delete();
        } catch (midcom_error $e) {
            return false;
        }
    }

    /**
     * Remove dir
     *
     * @param  string  $path  dir path
     * @return bool
     */
    protected function _rmdir($path)
    {
        $dir = new org_openpsa_documents_directory($path);
        return $dir->delete();
    }

    /**
     * Create new file and write into it from file pointer.
     * Return new file path or false on error.
     *
     * @param  resource  $fp   file pointer
     * @param  string    $dir  target dir path
     * @param  string    $name file name
     * @param  array     $stat file stat (required by some virtual fs)
     * @return bool|string
     */
    protected function _save($fp, $dir, $name, $stat)
    {
        $doc = $this->create_document($dir, $name);
        if (!$doc) {
            return false;
        }

        if (!$this->create_attachment($doc, $name, $stat['mime'], $fp)) {
            return false;
        }
        return $doc->guid;
    }

    private function create_attachment(org_openpsa_documents_document_dba $doc, $name, $mimetype, $fp)
    {
        $filename = midcom_db_attachment::safe_filename($name, true);
        $att = $doc->create_attachment($filename, $name, $mimetype);
        if (   !$att
            || !$att->copy_from_handle($fp)) {
            return false;
        }
        $identifier = md5(time() . $name);
        return $att->set_parameter('midcom.helper.datamanager2.type.blobs', 'fieldname', 'document')
            && $att->set_parameter('midcom.helper.datamanager2.type.blobs', 'identifier', 'document')
            && $doc->set_parameter('midcom.helper.datamanager2.type.blobs', "guids_document", $identifier . ':' . $att->guid);
    }

    /**
     * Get file contents
     *
     * @param  string  $path  file path
     * @return string|false
     */
    protected function _getContents($path)
    {
        throw new midcom_error('_getContents not implemented');
    }

    /**
     * Write a string to a file
     *
     * @param  string  $path     file path
     * @param  string  $content  new file content
     * @return bool
     */
    protected function _filePutContents($path, $content)
    {
        throw new midcom_error('_filePutContents not implemented');
    }

    /**
     * Extract files from archive
     *
     * @param  string  $path file path
     * @param  array   $arc  archiver options
     * @return bool
     */
    protected function _extract($path, $arc)
    {
        throw new midcom_error('_extract not implemented');
    }

    /**
     * Create archive and return its path
     *
     * @param  string  $dir    target dir
     * @param  array   $files  files names list
     * @param  string  $name   archive name
     * @param  array   $arc    archiver options
     * @return string|bool
     */
    protected function _archive($dir, $files, $name, $arc)
    {
        throw new midcom_error('_archive not implemented');
    }

    /**
     * Detect available archivers
     *
     * @return void
     */
    protected function _checkArchivers()
    {
        return;
    }

    /**
     * Change file mode (chmod)
     *
     * @param  string  $path  file path
     * @param  string  $mode  octal string such as '0755'
     * @return bool
     */
    protected function _chmod($path, $mode)
    {
        throw new midcom_error('_chmod not implemented');
    }
}
