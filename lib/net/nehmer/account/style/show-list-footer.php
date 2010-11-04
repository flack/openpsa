    </tbody>
</table>

<?php
if (isset($data['qb']))
{
    // Category lists are not paged
    echo $data['qb']->show_pages();
}
?>