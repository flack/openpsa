<?php
$nap = new midcom_helper_nav;
$node = $nap->get_node($nap->get_current_node());
?>
<h1><?php echo sprintf($data['l10n_midcom']->get('edit %s'), $data['l10n']->get('slideshow')); ?></h1>

<input type="file" multiple="multiple" id="upload_field" />
<input type="button" id="reverse" value="<?php echo $data['l10n']->get('reverse order'); ?>" />

<div id="item_container">
<?php
foreach ($data['images'] as $image)
{
    try
    {
        $attachment = new midcom_db_attachment($image->thumbnail);
        $url = midcom_db_attachment::get_url($attachment);
        $original = new midcom_db_attachment($image->attachment);
        $name = $original->name;
    }
    catch (midcom_error $e)
    {
        $url = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
        $name = $data['l10n']->get('attachment missing');
    }
    ?>
    <div class="entry existing-entry" id="image-&(image.guid);">
      <div class="thumbnail">
        <img src="&(url);" alt="&(name);" />
      </div>
      <div class="details">
        <span class="controls">
          <span class="action image-delete"></span>
          <span class="action image-cancel-delete"></span>
        </span>
        <span class="filename">&(name);</span>
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
  <span class="title"><input type="text" placeholder="<?php echo $data['l10n_midcom']->get('title'); ?>"/></span>
  <span class="description"><textarea rows="3" cols="40" placeholder="<?php echo $data['l10n_midcom']->get('description'); ?>"></textarea></span>
</div>
</div>
</div>

<div id="progress_bar"></div>

<form method="get" action="&(node[MIDCOM_NAV_FULLURL]);">
<input type="button" name="save_all" id="save_all" value="<?php echo $data['l10n']->get('save all'); ?>" />
<input type="submit" value="<?php echo $data['l10n_midcom']->get('back'); ?>" />
</form>