<?php
$link = $data['link'];
$page = $data['other_obj'];
$page_url = $data['page_url'] . $page->name . '/';
$author_card = org_openpsa_widgets_contact::get($page->metadata->creator);
?>
<li class="note" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title"><a href="&(page_url);" target="wiki_&(page.guid);">&(page.title);</a></span>
    <ul class="metadata">
      <li class="time"><?php echo $data['l10n']->get_formatter()->date($page->metadata->created); ?></li>
      <li class="members"><?php
        echo midcom::get()->i18n->get_string('author', 'net.nemein.wiki') . ': ' ;
        echo $author_card->show_inline(); ?>
      </li>
    </ul>

    <div id="org_openpsa_relatedto_details_url_&(page.guid);" style="display: none;" title="&(data['page_url']);raw/&(page.name);/"></div>
    <div id="org_openpsa_relatedto_details_&(page.guid);" class="details hidden" style="display: none;">
    </div>
    <?php org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']); ?>
</li>
