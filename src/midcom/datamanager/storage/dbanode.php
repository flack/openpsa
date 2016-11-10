<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom_core_dbaobject;

/**
 * Experimental storage baseclass
 */
abstract class dbanode implements node
{
    /**
     *
     * @var \midcom_core_dbaobject
     */
    protected $object;

    /**
     *
     * @var array
     */
    protected $config;

    public function __construct(midcom_core_dbaobject $object, array $config)
    {
        $this->object = $object;
        $this->config = $config;
    }
}
