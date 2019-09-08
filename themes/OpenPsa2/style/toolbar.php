<?php
if (midcom::get()->auth->can_user_do('midcom:centralized_toolbar', null, midcom_services_toolbars::class)) {
    $toolbars = midcom::get()->toolbars;
    $i18n = midcom::get()->i18n;
    $toolbar_class = "midcom_services_toolbars_simple";
    if (midcom::get()->auth->can_user_do('midcom:ajax', null, 'midcom_services_toolbars')) {
        $toolbar_class = "midcom_services_toolbars_fancy";
    } ?>
    <div class="&(toolbar_class);" style="display:none">
      <div class="minimizer"></div>
      <div class="items">
        <div id="midcom_services_toolbars_topic-folder" class="item">
          <span class="midcom_services_toolbars_topic_title folder"><?= $i18n->get_string('folder', 'midcom') ?></span>
          <?= $toolbars->render_node_toolbar(); ?>
        </div>
        <div id="midcom_services_toolbars_topic-host" class="item">
          <span class="midcom_services_toolbars_topic_title host"><?= $i18n->get_string('host', 'midcom') ?></span>
          <?= $toolbars->render_host_toolbar(); ?>
        </div>
        <div id="midcom_services_toolbars_topic-help" class="item">
          <span class="midcom_services_toolbars_topic_title help"><?= $i18n->get_string('help', 'midcom.admin.help') ?></span>
          <?= $toolbars->render_help_toolbar(); ?>
        </div>
      </div>
      <div class="dragbar"></div>
    </div>
<?php } ?>
