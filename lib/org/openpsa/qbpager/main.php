<?php
/**
 * @package org.openpsa.qbpager
 */

/**
 * Pages QB resultsets
 *
 * @package org.openpsa.qbpager
 */
class org_openpsa_qbpager extends midcom_baseclasses_components_purecode
{
    public $results_per_page = 25;
    public $display_pages = 10;
    public $string_next = 'next';
    public $string_previous = 'previous';
    protected $_midcom_qb;
    protected $_midcom_qb_count;
    protected $_pager_id;
    protected $_prefix = '';
    private $_offset = 0;
    private $count;
    private $_count_mode;
    private $_current_page = 1;

    public function __construct($classname, $pager_id)
    {
        if (empty($pager_id)) {
            throw new midcom_error('pager_id is not set (needed for distinguishing different instances on same request)');
        }
        parent::__construct();

        $this->_pager_id = $pager_id;
        $this->_prefix = 'org_openpsa_qbpager_' . $this->_pager_id . '_';
        $this->_prepare_qbs($classname);
    }

    protected function _prepare_qbs($classname)
    {
        $this->_midcom_qb = midcom::get()->dbfactory->new_query_builder($classname);
        // Make another QB for counting, we need to do this to avoid trouble with core internal references system
        $this->_midcom_qb_count = midcom::get()->dbfactory->new_query_builder($classname);
    }

    /**
     * Makes sure we have some absolutely required things properly set
     */
    protected function _sanity_check() : bool
    {
        if ($this->results_per_page < 1) {
            debug_add('this->results_per_page is set to ' . $this->results_per_page . ', aborting', MIDCOM_LOG_WARN);
            return false;
        }
        return true;
    }

    /**
     * Check $_REQUEST for variables and sets internal status accordingly
     */
    private function _check_page_vars()
    {
        $page_var = $this->_prefix . 'page';
        $results_var = $this->_prefix . 'results';
        if (!empty($_REQUEST[$page_var])) {
            debug_add("{$page_var} has value: {$_REQUEST[$page_var]}");
            $this->_current_page = (int)$_REQUEST[$page_var];
        }
        if (!empty($_REQUEST[$results_var])) {
            debug_add("{$results_var} has value: {$_REQUEST[$results_var]}");
            $this->results_per_page = (int)$_REQUEST[$results_var];
        }
        $this->_offset = ($this->_current_page-1)*$this->results_per_page;
        if ($this->_offset < 0) {
            $this->_offset = 0;
        }
    }

    /**
     * Get the current page number
     */
    public function get_current_page() : int
    {
        return $this->_current_page;
    }

    /**
     * Fetch all $_GET variables
     */
    private function _get_query_string(string $page_var, int $page_number) : string
    {
        $query = [$page_var => $page_number];

        foreach ($_GET as $key => $value) {
            if ($key != $page_var && $key != '') {
                $query[$key] = $value;
            }
        }

        return '?' . http_build_query($query);
    }

    /**
     * Displays previous/next selector
     */
    function show_previousnext()
    {
        $page_count = $this->count_pages();
        //Skip the header in case we only have one page
        if ($page_count <= 1) {
            return;
        }
        //@todo Move to style element
        //TODO: "showing results (offset)-(offset+limit)
        $page_var = $this->_prefix . 'page';
        echo '<div class="org_openpsa_qbpager_previousnext">';

        if ($this->_current_page > 1) {
            $previous = $this->_current_page - 1;
            echo "\n<a class=\"previous_page\" href=\"" . $this->_get_query_string($page_var, $previous) . "\" rel=\"prev\">" . $this->_l10n->get($this->string_previous) . "</a>";
        }

        if ($this->_current_page < $page_count) {
            $next = $this->_current_page + 1;
            echo "\n<a class=\"next_page\" href=\"" . $this->_get_query_string($page_var, $next) . "\" rel=\"next\">" . $this->_l10n->get($this->string_next) . "</a>";
        }

        echo "\n</div>\n";
    }

    public function get_pages() : array
    {
        $pages = [];
        $page_count = $this->count_pages();

        if ($page_count < 1) {
            return $pages;
        }

        $page_var = $this->_prefix . 'page';
        $display_start = max(($this->_current_page - ceil($this->display_pages / 2)), 1);
        $display_end = min(($this->_current_page + ceil($this->display_pages / 2)), $page_count);

        if ($this->_current_page > 1) {
            $previous = $this->_current_page - 1;
            if ($previous > 1) {
                $pages[] = [
                    'class' => 'first',
                    'href' => $this->_get_query_string($page_var, 1),
                    'rel' => 'prev',
                    'label' => $this->_l10n->get('first'),
                    'number' => 1
                ];
            }
            $pages[] = [
                'class' => 'previous',
                'href' => $this->_get_query_string($page_var, $previous),
                'rel' => 'prev',
                'label' => $this->_l10n->get($this->string_previous),
                'number' => $previous
            ];
        }
        $page = $display_start - 1;
        while ($page++ < $display_end) {
            $href = false;
            if ($page != $this->_current_page) {
                $href = $this->_get_query_string($page_var, $page);
            }
            $pages[] = [
                'class' => 'current',
                'href' => $href,
                'rel' => false,
                'label' => $page,
                'number' => $page
            ];
        }

        if ($this->_current_page < $page_count) {
            $next = $this->_current_page + 1;
            $pages[] = [
                'class' => 'next',
                'href' => $this->_get_query_string($page_var, $next),
                'rel' => 'next',
                'label' => $this->_l10n->get($this->string_next),
                'number' => $next
            ];

            if ($next < $page_count) {
                $pages[] = [
                    'class' => 'last',
                    'href' => $this->_get_query_string($page_var, $page_count),
                    'rel' => 'next',
                    'label' => $this->_l10n->get('last'),
                    'number' => $page_count
                ];
            }
        }

        return $pages;
    }

    private function show(string $name, array $data)
    {
        $context = midcom_core_context::enter();
        $context->set_custom_key('request_data', $data);
        midcom::get()->style->prepend_component_styledir($this->_component);
        midcom::get()->style->enter_context($context);
        midcom_show_style('show_' . $name);
        midcom::get()->style->leave_context();
        midcom_core_context::leave();
    }

    /**
     * Displays page selector
     */
    public function show_pages()
    {
        $this->show('pages', ['pages' => $this->get_pages()]);
    }

    /**
     * Displays page selector as list
     */
    function show_pages_as_list()
    {
        $this->show('pages_as_list', ['pages' => $this->get_pages()]);
    }

    /**
     * sets LIMIT and OFFSET for requested page
     */
    protected function _qb_limits($qb)
    {
        $this->_check_page_vars();

        if ($this->_current_page == 'all') {
            debug_add("displaying all results");
            return;
        }

        $qb->set_limit($this->results_per_page);
        $qb->set_offset($this->_offset);
        debug_add("set offset to {$this->_offset} and limit to {$this->results_per_page}");
    }

    public function execute()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->_qb_limits($this->_midcom_qb);
        return $this->_midcom_qb->execute();
    }

    public function execute_unchecked()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->_qb_limits($this->_midcom_qb);
        return $this->_midcom_qb->execute_unchecked();
    }

    /**
     * Returns number of total pages for query
     *
     * By default returns a number of pages without any ACL checks
     */
    public function count_pages()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->count_unchecked();
        return ceil($this->count / $this->results_per_page);
    }

    //Rest of supported methods wrapped with extra sanity check
    public function add_constraint($param, $op, $val)
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->_midcom_qb_count->add_constraint($param, $op, $val);
        return $this->_midcom_qb->add_constraint($param, $op, $val);
    }

    public function add_order($param, $sort='ASC')
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        return $this->_midcom_qb->add_order($param, $sort);
    }

    public function begin_group($type)
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->_midcom_qb_count->begin_group($type);
        $this->_midcom_qb->begin_group($type);
    }

    public function end_group()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        $this->_midcom_qb_count->end_group();
        $this->_midcom_qb->end_group();
    }

    public function include_deleted()
    {
        $this->_midcom_qb_count->include_deleted();
        $this->_midcom_qb->include_deleted();
    }

    public function count()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        if (   !$this->count
            || $this->_count_mode != 'count') {
            $this->count = $this->_midcom_qb_count->count();
        }
        $this->_count_mode = 'count';
        return $this->count;
    }

    public function count_unchecked()
    {
        if (!$this->_sanity_check()) {
            return false;
        }
        if (   !$this->count
            || $this->_count_mode != 'count_unchecked') {
            $this->count = $this->_midcom_qb_count->count_unchecked();
        }
        $this->_count_mode = 'count_unchecked';
        return $this->count;
    }
}
