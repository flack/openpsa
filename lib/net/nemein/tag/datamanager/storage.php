<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

use midcom\datamanager\storage\delayed;

/**
 * Experimental storage class
 */
class net_nemein_tag_datamanager_storage extends delayed
{
    private string $auto_context = '';

    public function __construct(midcom_core_dbaobject $object, array $config)
    {
        parent::__construct($object, $config);
        $this->auto_context = trim($this->config['type_config']['auto_context'] ?? '');
    }

    public function load()
    {
        if ($this->object->id) {
            $tags = net_nemein_tag_handler::get_object_tags($this->object);
            return net_nemein_tag_handler::tag_array2string($tags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $tag_array = net_nemein_tag_handler::string2tag_array((string) $this->value);
        if (!empty($this->auto_context)) {
            $new_tag_array = [];
            foreach ($tag_array as $tagname => $url) {
                $context = net_nemein_tag_handler::resolve_context($tagname);
                if (empty($context)) {
                    $tagname = "{$this->auto_context}:{$tagname}";
                }
                $new_tag_array[$tagname] = $url;
            }
            $tag_array = $new_tag_array;
        }
        if (!net_nemein_tag_handler::tag_object($this->object, $tag_array)) {
            debug_add("Tried to save the tags \"{$this->value}\" for field {$this->config['name']}, but failed. Ignoring silently.",
            MIDCOM_LOG_WARN);
        }
    }
}
