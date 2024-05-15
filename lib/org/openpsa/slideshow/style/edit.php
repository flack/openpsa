<?php
$action = $data['router']->generate('index');
?>
<h1><?php printf($data['l10n_midcom']->get('edit %s'), $data['l10n']->get('slideshow')); ?></h1>

<form method="get" action="&(action);">
<input type="file" multiple="multiple" id="upload_field" />
<input type="button" id="reverse" value="<?php echo $data['l10n']->get('reverse order'); ?>" />

<input type="button" name="save_all" id="save_all" value="<?php echo $data['l10n']->get('save all'); ?>" />
<input type="submit" value="<?php echo $data['l10n_midcom']->get('back'); ?>" />
</form>
<div class="slideshow-editor">

<div id="item_container">
<?php
foreach ($data['images'] as $image) {
    try {
        $attachment = new midcom_db_attachment($image->thumbnail);
        $url = midcom_db_attachment::get_url($attachment);
        $original = new midcom_db_attachment($image->attachment);
        $original_url = midcom_db_attachment::get_url($original);
        $name = $original->name;
    } catch (midcom_error) {
        $url = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
        $name = $data['l10n']->get('attachment missing');
        $original_url = '';
    } ?>
    <div class="entry existing-entry" id="image-&(image.guid);">
      <div class="thumbnail">
        <img src="&(url);" alt="&(name);" data-original-url="&(original_url);" />
      </div>
      <div class="details">
        <span class="controls">
          <span class="action image-delete"><i class="fa fa-trash"></i></span>
          <span class="action image-cancel-delete"><i class="fa fa-refresh"></i></span>
        </span>
        <span class="filename" title="&(name);">&(name);</span>
        <span class="title"><input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>" value="&(image.title);" /></span>
        <span class="description"><textarea rows="3" cols="40" placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>">&(image.description);</textarea></span>
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
  <span class="title">
    <input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>" value="" />
  </span>
  <span class="description">
    <textarea rows="3" cols="40" placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>"></textarea>
  </span>
</div>
</div>
</div>

<div id="entry-viewer">
  <div class="widget">
    <div class="image"></div>
    <div class="filename"></div>
    <div class="title">
      <input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>"/>
    </div>
    <div class="description">
      <textarea rows="3" cols="40" placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>"></textarea>
    </div>
  </div>
</div>

</div>

<div id="progress_dialog">
<div id="progress_bar"><div class="progress-label"></div></div>
<div class="progress-counter">
    <span class="progress-filesize" id="progress_filesize_total"></span>
    <span id="progress_completed"></span><span class="separator">/</span><span id="progress_total"></span>
</div>
</div>
