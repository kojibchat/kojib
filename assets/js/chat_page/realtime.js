var realtime_request = null;
var realtime_timeout = null;
var realtime_refresh_rate = $.trim(system_variable('refresh_rate'));
var site_notification_tone = $('.site_sound_notification > div > audio')[0];
var side_navigation = $('.main .side_navigation .menu_items');


if (realtime_refresh_rate.length === 0) {
    realtime_refresh_rate = 2000;
}

realtime_timeout = setTimeout(function() {
    realtime();
}, 3000);

function realtime() {

    if (realtime_timeout !== null) {
        clearTimeout(realtime_timeout);
    }

    var request_time = new Date($.now());
    var whos_typing_last_logged_user_id = 0;
    var logged_in_user_id = 0;
    var fetch_api_support = false;
    var force_disable_fetch = true;

    if (typeof fetch !== 'undefined' && typeof fetch === 'function' && force_disable_fetch === false) {
        fetch_api_support = true;
    }

    realtime_timeout = setTimeout(function() {

        var post_data = {
            request_time: request_time,
            realtime: true,
        };

        if ($('.logged_in_user_id').length > 0) {
            logged_in_user_id = $('.logged_in_user_id').text();
        }

        post_data['logged_in_user_id'] = logged_in_user_id;

        if (!$('.main .chatbox').hasClass('d-none') && !$('.main .chatbox > .contents > .chat_messages').hasClass('searching')) {

            if ($('.main .chatbox').attr('group_id') !== undefined) {
                post_data['group_id'] = $('.main .chatbox').attr('group_id');
            } else if ($('.main .chatbox').attr('user_id') !== undefined) {
                post_data['user_id'] = $('.main .chatbox').attr('user_id');
            }
            if ($('.main .chatbox').attr('group_id') !== undefined || $('.main .chatbox').attr('user_id') !== undefined) {
                post_data['message_id_greater_than'] = get_message_id('last');
                post_data['last_seen_by_recipient'] = get_message_id('last_seen_by_recipient');
            }

            if ($('.main .chatbox > .header > .heading > .whos_typing').attr('last_logged_user_id') !== undefined) {
                whos_typing_last_logged_user_id = $('.main .chatbox > .header > .heading > .whos_typing').attr('last_logged_user_id');
            }

            post_data['whos_typing_last_logged_user_id'] = whos_typing_last_logged_user_id;
        }

        if (side_navigation.find('li.realtime_module[module="groups"]').length > 0) {
            $unread_group_messages = 0;

            if (side_navigation.find('li.realtime_module[module="groups"]').attr('unread') != undefined) {
                $unread_group_messages = side_navigation.find('li.realtime_module[module="groups"]').attr('unread');
            }

            post_data['unread_group_messages'] = $unread_group_messages;
        }

        if (side_navigation.find('li.realtime_module[module="private_conversations"]').length > 0) {
            $unread_private_chat_messages = 0;

            if (side_navigation.find('li.realtime_module[module="private_conversations"]').attr('unread') != undefined) {
                $unread_private_chat_messages = side_navigation.find('li.realtime_module[module="private_conversations"]').attr('unread');
            }

            post_data['unread_private_chat_messages'] = $unread_private_chat_messages;
        }

        if (side_navigation.find('li.realtime_module[module="site_notifications"]').length > 0) {
            $unread_site_notifications = 0;

            if (side_navigation.find('li.realtime_module[module="site_notifications"]').attr('unread') != undefined) {
                $unread_site_notifications = side_navigation.find('li.realtime_module[module="site_notifications"]').attr('unread');
            }

            post_data['unread_site_notifications'] = $unread_site_notifications;
        }

        if (side_navigation.find('li.realtime_module[module="friends"]').length > 0) {
            $pending_friend_requests = 0;

            if (side_navigation.find('li.realtime_module[module="friends"]').attr('pending') != undefined) {
                $pending_friend_requests = side_navigation.find('li.realtime_module[module="friends"]').attr('pending');
            }

            post_data['pending_friend_requests'] = $pending_friend_requests;
        }

        if ($('.main .aside > .site_records > .current_record').attr('load') === 'online') {
            $recent_online_user_id = 0;
            $recent_online_user_online_status = 0;
            $total_online_users = 0;

            if (side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_id') != undefined) {
                $recent_online_user_id = side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_id');
            }

            if (side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_online_status') != undefined) {
                $recent_online_user_online_status = side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_online_status');
            }

            if (side_navigation.find('li.realtime_module[module="online_users"]').attr('total_online_users') != undefined) {
                $total_online_users = side_navigation.find('li.realtime_module[module="online_users"]').attr('total_online_users');
            }

            post_data['recent_online_user_id'] = $recent_online_user_id;
            post_data['recent_online_user_online_status'] = $recent_online_user_online_status;
            post_data['total_online_users'] = $total_online_users;
        }


        if (side_navigation.find('li.realtime_module[module="complaints"]').length > 0) {
            $unresolved_complaints = 0;

            if (side_navigation.find('li.realtime_module[module="complaints"]').attr('unresolved') != undefined) {
                $unresolved_complaints = side_navigation.find('li.realtime_module[module="complaints"]').attr('unresolved');
            }

            post_data['unresolved_complaints'] = $unresolved_complaints;
        }


        $last_realtime_log_id = 0;

        if ($('.main_window').attr('last_realtime_log_id') != undefined) {
            $last_realtime_log_id = $('.main_window').attr('last_realtime_log_id');
        }

        post_data['last_realtime_log_id'] = $last_realtime_log_id;

        if (fetch_api_support) {
            realtime_fetch_api(post_data);
        } else {
            realtime_ajax(post_data);
        }

    }, realtime_refresh_rate);
}

function realtime_fetch_api(post_data) {

    if (user_csrf_token !== null) {
        post_data["csrf_token"] = user_csrf_token;
    }

    let realtime_request = async () => {
        const response = await fetch(api_request_url, {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams(post_data)
        });
        if (response.status !== 200) {
            throw new Error("cannot fetch data");
        }
        let data = await response.json();
        return data;
    };
    realtime_request()
    .then((data) => {
        realtime_results(data, false);
    })
    .catch((err) => {
        console.log("rejected", err.message);
        realtime_request = null;
        realtime_timeout = null;
        realtime();
    });
}


function realtime_ajax(post_data) {

    if (user_csrf_token !== null) {
        post_data["csrf_token"] = user_csrf_token;
    }

    realtime_request = $.ajax({
        type: 'POST',
        url: api_request_url,
        data: post_data,
        async: true,
        beforeSend: function() {
            if (realtime_request !== null) {
                realtime_request.abort();
                realtime_request = null;
            }
        },
        success: function(data) {}
    }).done(function(data) {
        realtime_results(data);
    }) .fail(function(qXHR, textStatus, errorThrown) {
        if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
            console.log('ERROR : ' + errorThrown);
        }

        realtime_request = null;
        realtime_timeout = null;
        realtime();

    });
}

function realtime_results(data, parse_json) {

    var valid_json_data = true;


    if (parse_json === undefined) {
        if (isJSON(data)) {
            data = $.parseJSON(data);
        } else {
            valid_json_data = false;
        }
    }

    if (valid_json_data) {
        var aside_refresh = true;

        if (data.reload_page !== undefined) {
            location.reload(true);
        }

        if (data.play_sound_notification !== undefined && data.play_sound_notification) {
            site_notification_tone.currentTime = 0;
            site_notification_tone.play();
        }

        if ($('.main .aside > .site_records .current_record_search_keyword').val().length > 0) {
            aside_refresh = false;
        }

        if (!$('.main .aside > .site_records > .tools > .tool.multiple_selection').hasClass('d-none')) {
            aside_refresh = false;
        }

        if ($('.main .aside > .site_records .current_record_filter').val().length > 1) {
            aside_refresh = false;
        }

        if ($('.main .aside > .site_records .current_record_sort_by').val().length > 1) {
            aside_refresh = false;
        }

        if ($('.main .aside > .site_records > .current_record').hasClass('loading')) {
            aside_refresh = false;
        }

        var aside_scroll_position = $('.main .aside > .site_records > .records > .list').scrollTop();

        if (aside_scroll_position > 150) {
            aside_refresh = false;
        }

        if (!$('.main .chatbox').hasClass('d-none') && !$('.main .chatbox > .contents > .chat_messages').hasClass('searching')) {
            if (data.group_messages !== undefined) {
                data.group_messages.append = true;

                var scroll_position = $('.main .chatbox > .contents > .chat_messages').scrollTop();
                scroll_position = Math.abs(scroll_position);
                var screen_height = ($(window).height())-50;

                //console.log('scroll_position : ' + scroll_position + 'screen_height : ' + screen_height);

                if (scroll_position < screen_height) {
                    data.group_messages.scrollToBottom = true;
                }

                if (data.group_messages.messages !== undefined) {
                    if (data.group_messages.messages[0] !== undefined) {
                        if (data.group_messages.messages[0].own_message === undefined || !data.group_messages.messages[0].own_message) {
                            var browser_title = language_string('new_message_notification');
                            change_browser_title(browser_title, 5000);
                        }
                    }
                }

                load_messages(data.group_messages);
            }

            if (data.last_seen_by_recipient !== undefined) {
                if (data.last_seen_by_recipient.user_id !== undefined && $('.main .chatbox').attr('user_id') !== undefined) {
                    if ($('.main .chatbox').attr('user_id') == data.last_seen_by_recipient.user_id) {
                        if (data.last_seen_by_recipient.message_id !== undefined) {

                            var last_seen_by_recipient_id = parseInt(data.last_seen_by_recipient.message_id);

                            $('.main .chatbox > .contents > .chat_messages > ul > li').each(function() {
                                if ($(this).attr('message_id') != undefined && !$(this).hasClass('seen_by_recipient')) {

                                    var this_message_id = parseInt($(this).attr('message_id'));

                                    if (this_message_id <= last_seen_by_recipient_id) {
                                        $(this).addClass('seen_by_recipient');
                                        $(this).find('.read_status').addClass('read');
                                        $(this).find('.read_status').html('<i class="bi bi-check-all"></i>');
                                    }
                                }
                            });
                        }
                    }
                }
            }

            if (data.private_chat_messages !== undefined) {
                data.private_chat_messages.append = true;

                var scroll_position = $('.main .chatbox > .contents > .chat_messages').scrollTop();
                scroll_position = Math.abs(scroll_position);

                if (scroll_position < 300) {
                    data.private_chat_messages.scrollToBottom = true;
                }

                if (data.private_chat_messages.messages !== undefined) {
                    if (data.private_chat_messages.messages[0] !== undefined) {
                        if (data.private_chat_messages.messages[0].own_message === undefined || !data.private_chat_messages.messages[0].own_message) {
                            var browser_title = language_string('new_message_notification');
                            change_browser_title(browser_title, 5000);
                        }
                    }
                }

                load_messages(data.private_chat_messages);
            }

            if (data.users_typing !== undefined) {

                if (data.users_typing.last_inserted_user_id !== undefined) {
                    $('.main .chatbox > .header > .heading > .whos_typing').attr('last_logged_user_id', data.users_typing.last_inserted_user_id);
                }

                if (data.users_typing.users !== undefined) {
                    if (data.users_typing.group_id !== undefined && data.users_typing.group_id == $(".main .chatbox").attr('group_id')) {
                        whos_typing(data.users_typing.users);
                    } else if (data.users_typing.user_id !== undefined && data.users_typing.user_id == $(".main .chatbox").attr('user_id')) {
                        whos_typing(data.users_typing.users);
                    } else {
                        whos_typing(null);
                    }
                }

            }
        }

        if (data.unread_group_messages !== undefined) {

            if (data.unread_group_messages.length == 0) {
                data.unread_group_messages = 0;
            }

            var unread_text = '';

            side_navigation.find('li.realtime_module[module="groups"]').attr('unread', data.unread_group_messages);

            if (data.unread_group_messages !== 0) {
                unread_text = '<span>'+abbreviateNumber(data.unread_group_messages)+'</span>';

                if ($('.main .aside > .site_records > .current_record').attr('load') === 'groups') {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }

                }
            } else {
                if ($('.main .aside > .site_records > .current_record').attr('load') === 'groups') {
                    $(".main .aside > .site_records > .records > .list > li > div > .center > .title > .unread").addClass('d-none');
                }
            }

            side_navigation.find('li.realtime_module[module="groups"] > .menu_item > .unread').html(unread_text);
        }

        if (data.unread_private_chat_messages !== undefined) {

            if (data.unread_private_chat_messages.length == 0) {
                data.unread_private_chat_messages = 0;
            }


            var unread_text = '';
            side_navigation.find('li.realtime_module[module="private_conversations"]').attr('unread', data.unread_private_chat_messages);

            if (data.unread_private_chat_messages !== 0) {
                unread_text = '<span>'+abbreviateNumber(data.unread_private_chat_messages)+'</span>';

                if ($('.main .aside > .site_records > .current_record').attr('load') === 'private_conversations') {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }

                }
            } else {
                if ($('.main .aside > .site_records > .current_record').attr('load') === 'private_conversations') {
                    $(".main .aside > .site_records > .records > .list > li > div > .center > .title > .unread").addClass('d-none');
                }
            }

            $('.main .pm_shortcut > .notification_count').html(unread_text);
            side_navigation.find('li.realtime_module[module="private_conversations"] > .menu_item > .unread').html(unread_text);
        }

        if (data.unread_site_notifications !== undefined) {

            if (data.unread_site_notifications.length == 0) {
                data.unread_site_notifications = 0;
            }

            var unread_text = '';
            side_navigation.find('li.realtime_module[module="site_notifications"]').attr('unread', data.unread_site_notifications);

            if (data.unread_site_notifications !== 0) {
                unread_text = '<span>'+abbreviateNumber(data.unread_site_notifications)+'</span>';

                if ($('.main .aside > .site_records > .current_record').attr('load') === 'site_notifications') {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }

                }
            }
            side_navigation.find('li.realtime_module[module="site_notifications"] > .menu_item > .unread').html(unread_text);
        }


        if (data.pending_friend_requests !== undefined) {

            if (data.pending_friend_requests.length == 0) {
                data.pending_friend_requests = 0;
            }

            var unread_text = '';
            side_navigation.find('li.realtime_module[module="friends"]').attr('pending', data.pending_friend_requests);

            if (data.pending_friend_requests !== 0) {
                unread_text = '<span>'+abbreviateNumber(data.pending_friend_requests)+'</span>';

                if ($('.main .aside > .site_records > .current_record').attr('load') === 'friends') {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }

                }
            }
            side_navigation.find('li.realtime_module[module="friends"] > .menu_item > .unread').html(unread_text);
        }


        if (data.unresolved_complaints !== undefined) {

            if (data.unresolved_complaints.length == 0) {
                data.unresolved_complaints = 0;
            }

            var unread_text = '';
            side_navigation.find('li.realtime_module[module="complaints"]').attr('unresolved', data.unresolved_complaints);

            if (data.unresolved_complaints !== 0) {
                unread_text = '<span>'+abbreviateNumber(data.unresolved_complaints)+'</span>';

                if ($('.main .aside > .site_records > .current_record').attr('load') === 'complaints') {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }

                }
            }
            side_navigation.find('li.realtime_module[module="complaints"] > .menu_item > .unread').html(unread_text);
        }

        if (data.recent_online_user_id !== undefined) {
            if ($('.main .aside > .site_records > .current_record').attr('load') === 'online') {

                if (data.recent_online_user_id.length == 0) {
                    data.recent_online_user_id = 0;
                }

                if (data.recent_online_user_online_status.length == 0) {
                    data.recent_online_user_online_status = 0;
                }

                if (data.total_online_users.length == 0) {
                    data.total_online_users = 0;
                }

                var current_recent_online_user_id = 0;
                var current_online_user_online_status = 0;
                var total_online_users = 0;

                if (isFinite(side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_id'))) {
                    current_recent_online_user_id = side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_id');
                }

                if (side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_online_status') !== undefined) {
                    current_online_user_online_status = side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_online_status');
                }

                if (side_navigation.find('li.realtime_module[module="online_users"]').attr('total_online_users') !== undefined) {
                    total_online_users = side_navigation.find('li.realtime_module[module="online_users"]').attr('total_online_users');
                }

                side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_id', data.recent_online_user_id);
                side_navigation.find('li.realtime_module[module="online_users"]').attr('recent_online_user_online_status', data.recent_online_user_online_status);
                side_navigation.find('li.realtime_module[module="online_users"]').attr('total_online_users', data.total_online_users);

                if (data.total_online_users !== total_online_users || data.recent_online_user_id !== current_recent_online_user_id || data.recent_online_user_online_status !== current_online_user_online_status) {

                    if (aside_refresh) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                        load_aside($(".main .aside > .site_records > .current_record"));
                        $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                    }
                }
            }
        }

        if (data.unread_realtime_logs !== undefined) {

            if (data.last_realtime_log_id !== undefined) {
                $('.main_window').attr('last_realtime_log_id', data.last_realtime_log_id);
            }
            realtime_logs(data.unread_realtime_logs);
        }

        var total_unread_notifications = 0;
        var current_total_unread_notifications = 0;

        if ($('.total_unread_notifications').attr('total_unread_notification') !== undefined) {
            current_total_unread_notifications = parseInt($('.total_unread_notifications').attr('total_unread_notification'));
        }

        if (isFinite(side_navigation.find('li.realtime_module[module="groups"]').attr('unread'))) {
            total_unread_notifications = parseInt(total_unread_notifications)+parseInt(side_navigation.find('li.realtime_module[module="groups"]').attr('unread'));
        }

        if (isFinite(side_navigation.find('li.realtime_module[module="private_conversations"]').attr('unread'))) {
            total_unread_notifications = parseInt(total_unread_notifications)+parseInt(side_navigation.find('li.realtime_module[module="private_conversations"]').attr('unread'));
        }
        if (isFinite(side_navigation.find('li.realtime_module[module="site_notifications"]').attr('unread'))) {
            total_unread_notifications = parseInt(total_unread_notifications)+parseInt(side_navigation.find('li.realtime_module[module="site_notifications"]').attr('unread'));
        }
        if (isFinite(side_navigation.find('li.realtime_module[module="complaints"]').attr('unresolved'))) {
            total_unread_notifications = parseInt(total_unread_notifications)+parseInt(side_navigation.find('li.realtime_module[module="complaints"]').attr('unresolved'));
        }

        if (isFinite(side_navigation.find('li.realtime_module[module="friends"]').attr('pending'))) {
            total_unread_notifications = parseInt(total_unread_notifications)+parseInt(side_navigation.find('li.realtime_module[module="friends"]').attr('pending'));
        }

        if (current_total_unread_notifications != total_unread_notifications) {

            var set_browser_title = system_variable('current_title');

            if ($.trim(set_browser_title).length === 0) {
                set_browser_title = default_meta_title;
            }

            if (total_unread_notifications != 0) {
                $('.total_unread_notifications').html('<span>'+abbreviateNumber(total_unread_notifications)+'</span>');
                document.title = '[' + total_unread_notifications +'] ' + set_browser_title;
            } else {
                $('.total_unread_notifications').html('');
                document.title = set_browser_title;
            }

            $('.total_unread_notifications').attr('total_unread_notification', total_unread_notifications);
        }


    } else {
        console.log('ERROR : ' + data);
    }

    realtime_request = null;
    realtime_timeout = null;
    realtime();

}



function realtime_logs(realtime_logs) {
    $.each(realtime_logs, function(index, realtime_log) {
        if (realtime_log.log_type !== undefined && realtime_log.related_parameters !== undefined) {

            if (realtime_log.log_type === 'message_reaction') {
                realtime_log.related_parameters = $.parseJSON(realtime_log.related_parameters);

                if (realtime_log.related_parameters.total_reactions !== undefined) {
                    realtime_log.related_parameters.total_reactions = $.parseJSON(realtime_log.related_parameters.total_reactions);
                    update_message_reactions(realtime_log.related_parameters);
                }
            } else if (realtime_log.log_type === 'deleted_message') {
                realtime_log.related_parameters = $.parseJSON(realtime_log.related_parameters);

                if (realtime_log.related_parameters.message_id !== undefined) {
                    remove_messages(realtime_log.related_parameters);
                }
            } else if (realtime_log.log_type === 'removed_all_messages') {
                realtime_log.related_parameters = $.parseJSON(realtime_log.related_parameters);

                if (realtime_log.related_parameters.group_id !== undefined) {
                    if ($('.main .chatbox').attr('group_id') === realtime_log.related_parameters.group_id) {
                        $('.main .chatbox > .contents > .chat_messages > ul').html('');
                    }
                }
            }
        }
    });
}