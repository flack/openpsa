<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 PEAR packages type
 *
 * This type extends the blobs type in order to read PEAR data from the uploaded packages.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_pearpackages extends midcom_helper_datamanager2_type_blobs
{
    private $packagefile = null;
    private $package = null;

    /**
     * Add PEAR-specific information to the attachment info array
     */
    function _update_attachment_info_additional(&$info, $att)
    {
        $info['version'] = $att->get_parameter('midcom.helper.datamanager2.type.pearpackages', 'version');
        $info['notes'] = $att->get_parameter('midcom.helper.datamanager2.type.pearpackages', 'notes');
        $info['stability'] = $att->get_parameter('midcom.helper.datamanager2.type.pearpackages', 'stability');
        // TODO: Package.xml
    }

    /**
     * Makes sanity checks on the uploaded file, used by add_attachment and update_attachment
     *
     * @see add_attachment
     * @see update_attachment
     * @param string $tmpfile path to file to check
     * @param string $filename actual name of the file to use with attachment
     * @return boolean indicating sanity
     */
    function file_sanity_checks($tmpfile, $filename)
    {
        if ($this->_get_mimetype($tmpfile) != 'application/x-gzip')
        {
            debug_add("{$filename} is not a PEAR package", MIDCOM_LOG_WARN);

            $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), $this->_l10n->get('uploaded file is not a pear package'), 'error');

            return false;
        }

        // We need PEAR_PackageFile in order to read the package.xml format cleanly
        require_once 'PEAR/Config.php';
        require_once 'PEAR/PackageFile.php';

        // Read the package file
        $this->packagefile = new PEAR_PackageFile(PEAR_Config::singleton());
        $this->package = $this->packagefile->fromTgzFile($tmpfile, PEAR_VALIDATE_NORMAL);
        if ($this->package instanceof PEAR_Error)
        {
            debug_add("{$tmpfile} is not a PEAR package: " . $this->package->getMessage(), MIDCOM_LOG_WARN);

            $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), $this->_l10n->get('uploaded file is not a pear package'), 'error');

            return false;
        }

        if (   $this->storage->object
            && $this->storage->object->guid
            && is_a($this->storage->object, 'org_openpsa_products_product_dba')
            && $this->storage->object->code)
        {
            // This is an o.o.products product, ensure package name matches
            $package_parts = explode('-', basename($filename));
            if (count($package_parts) < 2)
            {
                var_dump($package_parts);
                debug_add("{$filename} has faulty name.", MIDCOM_LOG_WARN);

                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), $this->_l10n->get('uploaded file has faulty name'), 'error');

                return false;
            }

            if ($package_parts[0] != $this->storage->object->code)
            {
                debug_add("{$filename} doesn't match product name {$this->storage->object->code}.", MIDCOM_LOG_WARN);

                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), $this->_l10n->get("uploaded file doesn't match product name"), 'error');

                return false;
            }
        }

        return parent::file_sanity_checks($tmpfile, $filename);
    }

    /**
     * Read PEAR package information from uploaded files and add it to the attachment object
     *
     * @param string $identifier Attachment identifier
     * @param string $filename Original file name
     */
    function _set_attachment_info_additional($identifier, $filename)
    {
        if (   !$this->package
            || !is_a($this->package, 'PEAR_PackageFile_v2'))
        {
            return;
        }
        $this->attachments[$identifier]->set_parameter('midcom.helper.datamanager2.type.pearpackages', 'version', $this->package->getVersion());
        $this->attachments[$identifier]->set_parameter('midcom.helper.datamanager2.type.pearpackages', 'notes', $this->package->getNotes());
        $this->attachments[$identifier]->set_parameter('midcom.helper.datamanager2.type.pearpackages', 'stability', $this->package->getState());
        $this->attachments[$identifier]->set_parameter('midcom.helper.datamanager2.type.pearpackages', 'package.xml', file_get_contents($this->package->getPackageFile()));

        if (   $this->storage->object
            && $this->storage->object->guid)
        {
            $version = $this->storage->object->get_parameter('net.nemein.pearserver', 'version');

            if (   !$version
                || version_compare($this->package->getVersion(), $version, '>'))
            {
                // Cache the release information to the object if this is the latest release or a release doesn't exist
                $this->storage->object->set_parameter('net.nemein.pearserver', 'version', $this->package->getVersion());
                $this->storage->object->set_parameter('net.nemein.pearserver', 'notes', $this->package->getNotes());
                $this->storage->object->set_parameter('net.nemein.pearserver', 'stability', $this->package->getState());
            }
        }
    }

    function convert_to_html()
    {
        $result = '';
        if ($this->attachments_info)
        {
            $result .= "<ul>\n";
            foreach($this->attachments_info as $identifier => $info)
            {
                $result .= "<li><a href='{$info['url']}'>{$info['filename']}</a> ({$info['version']} {$info['stability']})<br />\n";
                $result .= $info['notes'];
                $result .= "</li>\n";
            }
            $result .= "</ul>\n";
        }
        return $result;
    }
}