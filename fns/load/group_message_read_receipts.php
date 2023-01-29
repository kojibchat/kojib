<?php
if (role(['permissions' => ['groups' => 'check_read_receipts']])) {
    $private_data["read_receipts"] = true;
    include('fns/load/group_members.php');
}
?>