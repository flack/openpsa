            <tr>
                <td>
                    <input type="checkbox" name="persons[]" value="<?php echo $data['person']->guid; ?>" />
                </td>
                <td>
                    <a href="<?php echo midcom_connection::get_url('uri') . $data['person']->guid; ?>/"><?php echo $data['person']->rname; ?></a>
                </td>
                <td>
                    <?php echo $data['person']->username; ?>
                </td>
                <td>
                    <?php echo $data['person']->email; ?>
                </td>
                <td>
                    <?php echo strftime('%x %H:%M', $data['person']->metadata->created); ?>
                </td>
            </tr>
