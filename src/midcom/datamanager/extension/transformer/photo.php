<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use midcom_db_attachment;

/**
 * Experimental photo transformer
 */
class photo extends blobs
{
    public function transform($input)
    {
        if ($input === null) {
            return;
        }
        $result = array();
        foreach ($input as $key => $value) {
            if ($value instanceof midcom_db_attachment) {
                //This is converting from storage
                $result[$key] = $this->transform_persistent($value);
            } elseif (!empty($value['object'])) {
                //This is during validation errors
                $result[$key] = $this->transform_persistent($value['object']);
            } elseif ($value !== null) {
                $result[$key] = $this->transform_nonpersistent(array('file' => $value, 'identifier' => 'archival'));
            }
        }
        return $result;
    }
}
