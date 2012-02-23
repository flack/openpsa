<input type="file" multiple="multiple" id="upload_field" />

<div id="item_container">
<?php
foreach ($data['images'] as $image)
{
    try
    {
        $attachment = new midcom_db_attachment($image->attachment);
        $url = midcom_db_attachment::get_url($attachment);
    }
    catch (midcom_error $e)
    {
        echo $e->getMessage();
        continue;
    }
    ?>
    <div class="entry existing-entry" id="image-&(image.guid);">
      <div class="thumbnail">
        <img src="&(url);" alt="&(attachment.name);" />
      </div>
      <div class="details">
        <span class="controls">
          <span class="action image-delete"></span>
          <span class="action image-cancel-delete"></span>
        </span>
        <span class="filename">&(attachment.name);</span>
        <span class="title"><input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>" value="&(image.title);" /></span>
        <span class="description"><textarea placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>">&(image.description);</textarea></span>
      </div>
    </div>
<?php } ?>

<div class="entry entry-template">
<div class="thumbnail">
</div>
<div class="details">
  <span class="controls">
    <span class="action image-delete"></span>
    <span class="action image-cancel-delete"></span>
  </span>
  <span class="filename"></span>
  <span class="title"><input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>"/></span>
  <span class="description"><textarea placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>"></textarea></span>
</div>
</div>
</div>

<div id="progress_bar"></div>
<input type="button" name="save_all" id="save_all" value="<?php echo $data['l10n']->get('save all'); ?>" />
