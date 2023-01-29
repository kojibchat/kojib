<?php

$data["last_seen_by_recipient"] = filter_var($data["last_seen_by_recipient"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["last_seen_by_recipient"])) {
    $data["last_seen_by_recipient"] = 0;
}

$columns = $join = $where = null;

$columns = ['private_conversations.private_conversation_id'];

$where["OR"]["AND #first_query"] = [
    "private_conversations.initiator_user_id" => $data["user_id"],
    "private_conversations.recipient_user_id" => Registry::load('current_user')->id,
];
$where["OR"]["AND #second_query"] = [
    "private_conversations.initiator_user_id" => Registry::load('current_user')->id,
    "private_conversations.recipient_user_id" => $data["user_id"],
];

$where["LIMIT"] = 1;
$private_conversation_id = DB::connect()->select('private_conversations', $columns, $where);

if (isset($private_conversation_id[0])) {
    $private_conversation_id = $private_conversation_id[0]['private_conversation_id'];

    $columns = $join = $where = null;
    $columns = ['private_chat_messages.private_chat_message_id'];
    $where = [
        'private_chat_messages.private_conversation_id' => $private_conversation_id,
        'private_chat_messages.read_status' => 1,
        'private_chat_messages.user_id' => Registry::load('current_user')->id,
        'ORDER' => ["private_chat_messages.private_chat_message_id" => "DESC"],
        "LIMIT" => 1
    ];

    $last_seen_by_recipient = DB::connect()->select('private_chat_messages', ['private_chat_message_id'], $where);

    if (isset($last_seen_by_recipient[0])) {
        $last_seen_by_recipient = $last_seen_by_recipient[0]['private_chat_message_id'];

        if ((int)$last_seen_by_recipient !== (int)$data["last_seen_by_recipient"]) {
            $result['last_seen_by_recipient']['user_id'] = $data["user_id"];
            $result['last_seen_by_recipient']['message_id'] = $last_seen_by_recipient;
            $escape = true;
        }
    }
}