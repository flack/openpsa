    <div class="person">
        <h2>
            <input id="person_<?php echo $data['person']->guid; ?>" type="checkbox" name="persons[]" value="<?php echo $data['person']->guid; ?>" checked="checked" />
            <label for="person_<?php echo $data['person']->guid; ?>"><?php echo $data['person']->rname; ?></label>
        </h2>
        <?php
        $data['datamanager']->display_view();
        ?>
    </div>
