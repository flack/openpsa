<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Metadata service.
 *
 * This service utilizes MidCOM's metadata system to provide meaningful, auto-generated
 * meta and link tags into documents. It is also entry point site builders can use to
 * retrieve metadata about current page.
 *
 * @package midcom.services
 */
class midcom_services_metadata
{
    /**
     * The metadata currently available. This array is indexed by context id; each
     * value consists of a metadata object. The metadata objects are created on-demand.
     *
     * @var midcom_helper_metadata[]
     */
    private $_metadata = [];

    /**
     * Class of the current page per each context.
     * Typically these are the same as the schema name of the current object's Datamanager schema.
     * This can be used for changing site styling based on body class="" etc.
     *
     * @var Array
     */
    private $_page_classes = [];

    /**
     * Returns the view metadata of the specified context.
     *
     * @param int $context_id The context to retrieve the view metadata for, this
     *     defaults to the current context.
     */
    public function get_view_metadata(int $context_id = null) : ?midcom_helper_metadata
    {
        if ($context_id === null) {
            $context_id = midcom_core_context::get()->id;
        }

        return $this->_metadata[$context_id] ?? null;
    }

    /**
     * Sets the class of the current page for a context
     */
    public function set_page_class(string $page_class)
    {
        $context = midcom_core_context::get();

        // Append current topic to page class if enabled
        if (midcom::get()->config->get('page_class_include_component')) {
            $page_class .= ' ' . str_replace('.', '_', $context->get_key(MIDCOM_CONTEXT_COMPONENT));
        }

        // Append a custom class from topic to page class
        $topic_class = $context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC)->get_parameter('midcom.services.metadata', 'page_class');
        if (!empty($topic_class)) {
            $page_class .= " {$topic_class}";
        }

        $this->_page_classes[$context->id] = trim($page_class);
    }

    /**
     * Gets the CSS class of the current page of a context
     */
    public function get_page_class() : string
    {
        $context = midcom_core_context::get();

        if (array_key_exists($context->id, $this->_page_classes)) {
            return $this->_page_classes[$context->id];
        }
        return 'default';
    }

    /**
     * Get CSS classes for an object. This will include two new CSS classes for the object's class string
     * if appropriate:
     *
     * - unapproved: approvals are enabled for the site but the object is not translated
     * - hidden: object is hidden via metadata settings or scheduling
     *
     * @param midcom_core_dbaobject $object The DBA class instance to get CSS classes for
     * @param string $existing_classes Existing CSS classes to append to
     */
    public function get_object_classes($object, string $existing_classes = null) : string
    {
        $css_classes = [];
        if ($existing_classes !== null) {
            $css_classes[] = $existing_classes;
        }

        // Approval attributes
        if (   midcom::get()->config->get('metadata_approval')
            && !$object->metadata->is_approved()) {
            $css_classes[] = 'unapproved';
        }

        // Hiding and scheduling attributes
        if (   (   !midcom::get()->config->get('show_hidden_objects')
                || midcom::get()->config->get('metadata_scheduling'))
            && !$object->metadata->is_visible()) {
            $css_classes[] = 'hidden';
        }

        // Folder's class
        if (   $object instanceof midcom_db_topic
            && $page_class = $object->get_parameter('midcom.services.metadata', 'page_class')) {
            $css_classes[] = $page_class;
        }

        return implode(' ', $css_classes);
    }

    /**
     * Binds view metadata to a DBA content object
     */
    public function bind_to(midcom_core_dbaobject $object)
    {
        $context = midcom_core_context::get();

        $this->_metadata[$context->id] = midcom_helper_metadata::retrieve($object);
        if (!$this->_metadata[$context->id]) {
            return;
        }

        // Update request metadata if appropriate
        $request_metadata = $this->get_request_metadata($context);
        $edited = $this->_metadata[$context->id]->get('revised');
        if ($edited > $request_metadata['lastmodified']) {
            $this->set_request_metadata($edited, $request_metadata['permalinkguid']);
        }
    }

    /**
     * Populates appropriate metadata into HTML documents based on metadata information
     * available to MidCOM for the request.
     */
    public function populate_meta_head()
    {
        // HTML generator information
        midcom::get()->head->add_meta_head(['name' => 'generator', 'content' => 'MidCOM']);

        // If an object has been bound we have more information available
        if ($view_metadata = $this->get_view_metadata()) {
            foreach (midcom::get()->config->get('metadata_head_elements') as $property => $metatag) {
                if ($content = $view_metadata->get($property)) {
                    // Handle date fields
                    if (in_array($property, ['published', 'created', 'revised', 'approved', 'locked'])) {
                        $content = gmdate('Y-m-d', (int) $content);
                    }

                    midcom::get()->head->add_meta_head([
                        'name' => $metatag,
                        'content' => $content,
                    ]);
                }
            }
            // TODO: Add support for tags here

            if (midcom::get()->config->get('metadata_opengraph')) {
                $this->_add_opengraph_metadata($view_metadata);
            }
        }
    }

    private function _add_opengraph_metadata(midcom_helper_metadata $view_metadata)
    {
        $opengraph_type = $view_metadata->object->get_parameter('midcom.helper.metadata', 'opengraph_type');
        if (   $opengraph_type
            && $opengraph_type != 'none') {
            $request_metadata = $this->get_request_metadata();

            midcom::get()->head->add_meta_head([
                'property' => 'og:type',
                'content' => $opengraph_type,
            ]);
            midcom::get()->head->add_meta_head([
                'property' => 'og:title',
                'content' => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE),
            ]);
            midcom::get()->head->add_meta_head([
                'property' => 'og:url',
                'content' => $request_metadata['permalink'],
            ]);
            $opengraph_image = $view_metadata->object->get_parameter('midcom.helper.metadata', 'opengraph_image');
            if (mgd_is_guid($opengraph_image)) {
                midcom::get()->head->add_meta_head([
                    'property' => 'og:image',
                    'content' => midcom_db_attachment::get_url($opengraph_image),
                ]);
            }
            midcom::get()->head->add_meta_head([
                'property' => 'og:description',
                'content' => $view_metadata->get('description'),
            ]);
        }
    }

    /**
     * Return a list of Open Graph Protocol types
     *
     * @see http://opengraphprotocol.org/
     */
    public function get_opengraph_types() : array
    {
        if (!midcom::get()->config->get('metadata_opengraph')) {
            return [];
        }

        return [
            'none' => 'opengraph type select',
            'activity' => 'opengraph activity activity',
            'sport' => 'opengraph activity sport',
            'bar' => 'opengraph business bar',
            'company' => 'opengraph business company',
            'hotel' => 'opengraph business hotel',
            'restaurant' => 'opengraph business restaurant',
            'cause' => 'opengraph group cause',
            'sports_league' => 'opengraph group sports_league',
            'sports_team' => 'opengraph group sports_team',
            'band' => 'opengraph organization band',
            'government' => 'opengraph organization government',
            'non_profit' => 'opengraph organization non_profit',
            'school' => 'opengraph organization school',
            'university' => 'opengraph organization university',
            'actor' => 'opengraph person actor',
            'athlete' => 'opengraph person athlete',
            'author' => 'opengraph person author',
            'director' => 'opengraph person director',
            'musician' => 'opengraph person musician',
            'politician' => 'opengraph person politician',
            'public_figure' => 'opengraph person public_figure',
            'city' => 'opengraph place city',
            'country' => 'opengraph place country',
            'landmark' => 'opengraph place landmark',
            'state_province' => 'opengraph place state_province',
            'album' => 'opengraph product album',
            'book' => 'opengraph product book',
            'drink' => 'opengraph product drink',
            'food' => 'opengraph product food',
            'game' => 'opengraph product game',
            'movie' => 'opengraph product movie',
            'product' => 'opengraph product product',
            'song' => 'opengraph product song',
            'tv_show' => 'opengraph product tv_show',
            'article' => 'opengraph website article',
            'blog' => 'opengraph website blog',
            'website' => 'opengraph website website',
        ];
    }

    /**
     * Get the default Open Graph Protocol type for an object
     */
    public function get_opengraph_type_default() : string
    {
        if (!midcom::get()->config->get('metadata_opengraph')) {
            return '';
        }

        $context = midcom_core_context::get();
        if (empty($this->_metadata[$context->id])) {
            return '';
        }
        $object = $this->_metadata[$context->id]->object;

        $component = $context->get_key(MIDCOM_CONTEXT_COMPONENT);
        if (!$component) {
            return '';
        }

        $interface = midcom::get()->componentloader->get_interface_class($component);
        if (!method_exists($interface, 'get_opengraph_default')) {
            return '';
        }

        return $interface->get_opengraph_default($object);
    }

    /**
     * Set the currently known and required Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * You may set either of the arguments to null to enforce default usage (based on NAP).
     */
    public function set_request_metadata(int $lastmodified, ?string $permalinkguid)
    {
        $context = midcom_core_context::get();

        $context->set_key(MIDCOM_CONTEXT_LASTMODIFIED, $lastmodified);
        $context->set_key(MIDCOM_CONTEXT_PERMALINKGUID, $permalinkguid);
    }

    /**
     * Get the currently known and required Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * @param midcom_core_context $context The context from which the request metadata should be retrieved. Omit
     *     to use the current context.
     * @return Array An array with the two keys 'lastmodified' and 'permalinkguid' containing the
     *     values set with the setter pendant. For ease of use, there is also a key 'permalink'
     *     which contains a ready-made permalink.
     */
    public function get_request_metadata(midcom_core_context $context = null) : array
    {
        if ($context === null) {
            $context = midcom_core_context::get();
        }
        return [
            'lastmodified' => $context->get_key(MIDCOM_CONTEXT_LASTMODIFIED),
            'permalinkguid' => $context->get_key(MIDCOM_CONTEXT_PERMALINKGUID),
            'permalink' => midcom::get()->permalinks->create_permalink($context->get_key(MIDCOM_CONTEXT_PERMALINKGUID)),
        ];
    }
}
