<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class photo extends image
{
    protected bool $save_archival = true;
}
