<?php
use Medoo\Medoo;

$data["unread_group_messages"] = filter_var($data["unread_group_messages"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["unread_group_messages"])) {
    $data["unread_group_messages"] = 0;
}

$super_privileges = false;

if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}


$column = $join = $where = null;
$sql_statement = '';

$columns = [
    'groups.group_id', 'group_members.last_read_message_id'
];

$sql_statement .= '(SELECT count(<group_message_id>) FROM <group_messages> ';

$sql_statement .= 'LEFT JOIN <site_users_blacklist> AS blacklist ON <group_messages.user_id> = blacklist.blacklisted_user_id ';
$sql_statement .= 'AND blacklist.user_id = '.Registry::load('current_user')->id.' ';
$sql_statement .= 'LEFT JOIN <site_users_blacklist> AS blocked ON <group_messages.user_id> = blocked.user_id ';
$sql_statement .= 'AND blocked.blacklisted_user_id = '.Registry::load('current_user')->id.' ';

$sql_statement .= 'WHERE <group_members.last_read_message_id> IS NOT NULL AND <group_id>=<groups.group_id> ';
$sql_statement .= 'AND ((blacklist.ignore IS NULL OR blacklist.ignore = 0) ';

if (!$super_privileges) {
    $sql_statement .= 'AND (blocked.block IS NULL OR blocked.block = 0) ';
}

$sql_statement .= 'AND (blacklist.block IS NULL OR blacklist.block = 0)) ';
$sql_statement .= 'AND  <group_messages.user_id> != '.Registry::load('current_user')->id.' ';
$sql_statement .= 'AND  <group_message_id> > <group_members.last_read_message_id>)';

$columns['unread_messages'] = Medoo::raw($sql_statement);

$join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
$join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

$where["group_members.last_read_message_id[!]"] = NULL;
$where["group_members.group_role_id[!]"] = NULL;
$where["group_roles.group_role_attribute[!]"] = 'banned_users';
$where["group_members.user_id"] = Registry::load('current_user')->id;

$where["LIMIT"] = Registry::load('settings')->records_per_call;

$groups = DB::connect()->select('groups', $join, $columns, $where);

$unread_group_messages = 0;

foreach ($groups as $group) {

    if (!empty($group['unread_messages'])) {
        $unread_group_messages = $group['unread_messages']+$unread_group_messages;
    }
}

if ((int)$unread_group_messages !== (int)$data["unread_group_messages"]) {
    $result['unread_group_messages'] = $unread_group_messages;

    if (isset(Registry::load('settings')->play_notification_sound->on_group_unread_count_change)) {
        if ($unread_group_messages > $data["unread_group_messages"]) {
            $result['play_sound_notification'] = true;
        }
    }

    $escape = true;
}