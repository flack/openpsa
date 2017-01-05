<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog Archive pages handler
 *
 * Shows the various archive views.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_archive extends midcom_baseclasses_components_handler
{
    /**
     * The articles to display
     *
     * @var array
     */
    private $_articles = null;

    /**
     * The datamanager for the currently displayed article.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * The start date of the Archive listing.
     *
     * @var DateTime
     */
    private $_start = null;

    /**
     * The end date of the Archive listing.
     *
     * @var DateTime
     */
    private $_end = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['datamanager'] = $this->_datamanager;
        $this->_request_data['start'] = $this->_start;
        $this->_request_data['end'] = $this->_end;
    }

    /**
     * Shows the archive welcome page: A listing of years/months along with total post counts
     * and similar stuff.
     *
     * The handler computes all necessary data and populates the request array accordingly.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_welcome($handler_id, array $args, array &$data)
    {
        $this->_compute_welcome_data();
        $this->_prepare_request_data();

        if ($this->_config->get('archive_in_navigation')) {
            $this->set_active_leaf($this->_topic->id . '_ARCHIVE');
        }

        midcom::get()->head->set_pagetitle("{$this->_topic->extra}: " . $this->_l10n->get('archive'));

        midcom::get()->metadata->set_request_metadata(net_nehmer_blog_viewer::get_last_modified($this->_topic), $this->_topic->guid);
    }

    /**
     * Loads the first posting time from the DB. This is the base for all operations on the
     * resultset.
     *
     * This is done under sudo if possible, to avoid problems arising if the first posting
     * is hidden. This keeps up performance, as an execute_unchecked() can be made in this case.
     * If sudo cannot be acquired, the system falls back to excute().
     *
     * @return DateTime The time of the first posting or null on failure.
     */
    private function _compute_welcome_first_post()
    {
        $qb = midcom_db_article::new_query_builder();
        $this->_master->article_qb_constraints($qb, 'archive_welcome');
        $qb->add_constraint('metadata.published', '>', '1970-01-02 23:59:59');

        $qb->add_order('metadata.published');
        $qb->set_limit(1);

        if (midcom::get()->auth->request_sudo($this->_component)) {
            $result = $qb->execute_unchecked();
            midcom::get()->auth->drop_sudo();
        } else {
            $result = $qb->execute();
        }

        if (!empty($result)) {
            return new DateTime(strftime('%Y-%m-%d %H:%M:%S', $result[0]->metadata->published));
        }
        return null;
    }

    /**
     * Computes the number of postings in a given timeframe.
     *
     * @param DateTime $start Start of the timeframe (inclusive)
     * @param DateTime $end End of the timeframe (exclusive)
     * @return int Posting count
     */
    private function _compute_welcome_posting_count($start, $end)
    {
        $data =& $this->_request_data;
        $qb = midcom_db_article::new_query_builder();

        $qb->add_constraint('metadata.published', '>=', $start->format('Y-m-d H:i:s'));
        $qb->add_constraint('metadata.published', '<', $end->format('Y-m-d H:i:s'));
        $this->_master->article_qb_constraints($qb, 'archive_welcome');

        return $qb->count();
    }

    /**
     * Computes the data nececssary for the welcome screen. Automatically put into the request
     * data array.
     */
    private function _compute_welcome_data()
    {
        // Helpers
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'archive/';

        // First step of request data: Overall info
        $total_count = 0;
        $year_data = array();
        $first_post = $this->_compute_welcome_first_post();
        $this->_request_data['first_post'] = $first_post;
        $this->_request_data['total_count'] =& $total_count;
        $this->_request_data['year_data'] =& $year_data;
        if (!$first_post) {
            return;
        }

        // Second step of request data: Years and months.
        $now = new DateTime();
        $first_year = $first_post->format('Y');
        $last_year = $now->format('Y');

        $month_names = $this->_get_month_names();

        for ($year = $last_year; $year >= $first_year; $year--) {
            $year_url = "{$prefix}year/{$year}/";
            $year_count = 0;
            $month_data = array();

            // Loop over the months, start month is either first posting month
            // or January in all other cases. End months are treated similarly,
            // being december by default unless for the current year.
            if ($year == $first_year) {
                $first_month = $first_post->format('n');
            } else {
                $first_month = 1;
            }

            if ($year == $last_year) {
                $last_month = $now->format('n');
            } else {
                $last_month = 12;
            }

            for ($month = $first_month; $month <= $last_month; $month++) {
                $start_time = new DateTime();
                $start_time->setDate($year, $month, 1);
                $end_time = clone $start_time;
                $end_time->modify('+1 month');

                $month_url = "{$prefix}month/{$year}/{$month}/";
                $month_count = $this->_compute_welcome_posting_count($start_time, $end_time);
                $year_count += $month_count;
                $total_count += $month_count;
                $month_data[$month] = array(
                    'month' => $month,
                    'name' => $month_names[$month],
                    'url' => $month_url,
                    'count' => $month_count,
                );
            }

            $year_data[$year] = array(
                'year' => $year,
                'url' => $year_url,
                'count' => $year_count,
                'month_data' => $month_data,
            );
        }
    }

    private function _get_month_names()
    {
        $names = array();
        for ($i = 1; $i < 13; $i++) {
            $timestamp = mktime(0, 0, 0, $i, 1, 2011);
            $names[$i] = strftime('%B', $timestamp);
        }
        return $names;
    }

    /**
     * Displays the welcome page.
     *
     * Element sequence:
     *
     * - archive-welcome-start (Start of the archive welcome page)
     * - archive-welcome-year (Display of a single year, may not be called when there are no postings)
     * - archive-welcome-end (End of the archive welcome page)
     *
     * Context data for all elements:
     *
     * - int total_count (total number of postings w/o ACL restrictions)
     * - DateTime first_post (the first posting date, may be null)
     * - Array year_data (the year data, contains the year context info as outlined below)
     *
     * Context data for year elements:
     *
     * - int year (the year displayed)
     * - string url (url to display the complete year)
     * - int count (Number of postings in that year)
     * - array month_data (the monthly data)
     *
     * month_data will contain an associative array containing the following array of data
     * indexed by month number (1-12):
     *
     * - string 'url' => The URL to the month.
     * - string 'name' => The localized name of the month.
     * - int 'count' => The number of postings in that month.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_welcome($handler_id, array &$data)
    {
        midcom_show_style('archive-welcome-start');

        foreach ($data['year_data'] as $year => $year_data) {
            $data['year'] = $year;
            $data['url'] = $year_data['url'];
            $data['count'] = $year_data['count'];
            $data['month_data'] = $year_data['month_data'];
            midcom_show_style('archive-welcome-year');
        }

        midcom_show_style('archive-welcome-end');
    }

    /**
     * Shows the archive. Depending on the selected handler various constraints are added to
     * the QB. See the add_*_constraint methods for details.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        // Get Articles, distinguish by handler.
        $qb = midcom_db_article::new_query_builder();
        $this->_master->article_qb_constraints($qb, $handler_id);

        // Use helper functions to determine start/end
        switch ($handler_id) {
            case 'archive-year-category':
                $data['category'] = trim(strip_tags($args[1]));
                if (   isset($data['schemadb']['default']->fields['categories'])
                    && array_key_exists('allow_multiple', $data['schemadb']['default']->fields['categories']['type_config'])
                    && !$data['schemadb']['default']->fields['categories']['type_config']['allow_multiple']) {
                    $qb->add_constraint('extra1', '=', (string) $data['category']);
                } else {
                    $qb->add_constraint('extra1', 'LIKE', "%|{$this->_request_data['category']}|%");
                }
                //Fall-through

            case 'archive-year':
                if (!$this->_config->get('archive_years_enable')) {
                    throw new midcom_error_notfound('Year archive not allowed');
                }

                $this->_set_startend_from_year($args[0]);
                break;

            case 'archive-month':
                $this->_set_startend_from_month($args[0], $args[1]);
                break;

            default:
                throw new midcom_error("The request handler {$handler_id} is not supported.");
        }

        $qb->add_constraint('metadata.published', '>=', $this->_start->format('Y-m-d H:i:s'));
        $qb->add_constraint('metadata.published', '<', $this->_end->format('Y-m-d H:i:s'));
        $qb->add_order('metadata.published', $this->_config->get('archive_item_order'));
        $this->_articles = $qb->execute();

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        // Move end date one day backwards for display purposes.
        $now = new DateTime();
        if ($now < $this->_end) {
            $this->_end = $now;
        } else {
            $this->_end->modify('-1 day');
        }

        $timeframe = $this->_l10n->get_formatter()->timeframe($this->_start, $this->_end, 'date');
        $this->add_breadcrumb("archive/year/{$args[0]}/", $timeframe);

        $this->_prepare_request_data();

        if ($this->_config->get('archive_in_navigation')) {
            $this->set_active_leaf($this->_topic->id . '_ARCHIVE');
        } else {
            $this->set_active_leaf($this->_topic->id . '_ARCHIVE_' . $args[0]);
        }

        midcom::get()->metadata->set_request_metadata(net_nehmer_blog_viewer::get_last_modified($this->_topic), $this->_topic->guid);
        midcom::get()->head->set_pagetitle("{$this->_topic->extra}: {$timeframe}");
    }

    /**
     * Computes the start/end dates to only query a given year. It will do validation
     * before processing, throwing 404 in case of incorrectly formatted dates.
     *
     * This is used by the archive-year handler, which expects the year to be in $args[0].
     *
     * @param int $year The year to query.
     */
    private function _set_startend_from_year($year)
    {
        if (strlen($year) != 4) {
            throw new midcom_error_notfound("The year '{$year}' is not a valid year identifier.");
        }

        $now = new DateTime();
        if ($year > (int) $now->format('Y')) {
            throw new midcom_error_notfound("The year '{$year}' is in the future, no archive available.");
        }

        $endyear = $year + 1;
        $this->_start = new DateTime("{$year}-01-01 00:00:00");
        $this->_end = new DateTime("{$endyear}-01-01 00:00:00");
    }

    /**
     * Computes the start/end dates to only query a given month. It will do validation
     * before processing, throwing 404 in case of incorrectly formatted dates.
     *
     * This is used by the archive-month handler, which expects the year to be in $args[0]
     * and the month to be in $args[1].
     *
     * @param int $year The year to query.
     * @param int $month The month to query.
     */
    private function _set_startend_from_month($year, $month)
    {
        if (strlen($year) != 4) {
            throw new midcom_error_notfound("The year '{$year}' is not a valid year identifier.");
        }

        if (   $month < 1
            || $month > 12) {
            throw new midcom_error_notfound("The year {$month} is not a valid year identifier.");
        }

        $now = new DateTime();
        $this->_start = new DateTime("{$year}-" . sprintf('%02d', $month) .  "-01 00:00:00");
        if ($this->_start > $now) {
            throw new midcom_error_notfound("The month '{$year}-" . sprintf('%02d', $month) .  "' is in the future, no archive available.");
        }

        if ($month == 12) {
            $endyear = $year + 1;
            $endmonth = 1;
        } else {
            $endyear = $year;
            $endmonth = $month + 1;
        }

        $this->_end = new DateTime("{$endyear}-" . sprintf('%02d', $endmonth) .  "-01 00:00:00");
    }

    /**
     * Displays the archive.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        // FIXME: For some reason the config topic is lost between _handle and _show phases
        $this->_config->store_from_object($this->_topic, $this->_component);

        midcom_show_style('archive-list-start');
        if ($this->_articles) {
            $data['index_fulltext'] = $this->_config->get('index_fulltext');
            $data['comments_enable'] = (boolean) $this->_config->get('comments_enable');

            $total_count = count($this->_articles);
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

            foreach ($this->_articles as $article_counter => $article) {
                if (!$this->_datamanager->autoset_storage($article)) {
                    debug_add("The datamanager for article {$article->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $article);
                    continue;
                }

                $data['article'] = $article;
                $data['article_counter'] = $article_counter;
                $data['article_count'] = $total_count;
                $data['view_url'] = $prefix . $this->_master->get_url($article, $this->_config->get('link_to_external_url'));
                $data['local_view_url'] = $data['view_url'];
                $data['linked'] = ($article->topic !== $this->_topic->id);
                if ($data['linked']) {
                    $nap = new midcom_helper_nav();
                    $data['node'] = $nap->get_node($article->topic);
                }

                midcom_show_style('archive-list-item');
            }
        } else {
            midcom_show_style('archive-list-empty');
        }

        midcom_show_style('archive-list-end');
    }
}
