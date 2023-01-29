$("body").on('click', '.api_request', function(e) {

    if (!$(this).hasClass('processing')) {
        $(this).addClass('processing');

        var data = $(this).data();
        var element = $(this);
        var skip_hide_action = false;
        var column = 'first';

        if (element.attr('column') !== undefined) {
            column = element.attr('column');
        }

        if ($(this).attr('loader') !== undefined) {
            $($(this).attr('loader')).show();
        }

        if (user_csrf_token !== null) {
            data['csrf_token'] = user_csrf_token;
        }

        if ($(this).attr('multi_select') !== undefined) {

            if ($(this).attr('data-chat_messages') !== undefined) {

                if ($(".main .chatbox").attr('group_id') !== undefined) {
                    data['remove'] = "group_messages";
                } else if ($(".main .chatbox").attr('user_id') !== undefined) {
                    data['remove'] = "private_chat_messages";
                }

                data['message_id'] = new Array();
                $(".main .chatbox .selector.select_item > input:checked").each(function() {
                    data['message_id'].push($(this).val());
                });
            } else {
                var selected = new Array();
                $(".main .aside > .site_records > .records > .list > li > div > .selector.select_item > input:checked").each(function() {
                    selected.push($(this).val());
                });
                data[$(this).attr('multi_select')] = selected;
            }

        }

        $.ajax({
            type: 'POST',
            url: api_request_url,
            data: data,
            async: true,
            success: function(data) {}
        }).done(function(data) {
            if (data === '') {
                location.reload(true);
            } else if (isJSON(data)) {
                data = $.parseJSON(data);
                if (data.success) {

                    if (data.force_reload_aside !== undefined) {
                        $('.main .aside > .site_records > .current_record').attr('load', data.force_reload_aside);
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');

                        if (data.filter_data !== undefined) {
                            $(".main .aside > .site_records > .current_record > .title").attr('filter_data', data.filter_data);
                        }

                        $(".main .aside > .site_records > .current_record > .title").trigger('click');
                    }

                    if (data.reload !== undefined && $.isArray(data.reload)) {
                        if (jQuery.inArray($('.main .aside > .site_records > .current_record').attr('load'), data.reload) !== -1) {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                            $(".main .aside > .site_records > .current_record > .title").trigger('click');
                        }
                    } else if (data.todo == 'reload') {

                        if (data.reload !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === data.reload) {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');

                            if (data.filter_data !== undefined) {
                                $(".main .aside > .site_records > .current_record > .title").attr('filter_data', data.filter_data);
                            }

                            $(".main .aside > .site_records > .current_record > .title").trigger('click');
                        }

                    } else if (data.todo == 'refresh') {
                        window.location.href = baseurl;
                    } else if (data.todo == 'refresh_current_page') {
                        location.reload(true);
                    } else if (data.todo == 'consolelog' && data.log !== undefined) {
                        console.log(data.log);
                    } else if (data.todo == 'redirect') {
                        window.location.href = data.redirect;
                    } else if (data.todo == 'update_message_reactions') {
                        if (data.update_data !== undefined) {
                            update_message_reactions(data.update_data);
                        }
                    } else if (data.todo == 'remove_messages') {

                        $(".main .chatbox > .header > .message_selection").find('input').prop('checked', false);

                        if (data.remove_data !== undefined) {
                            remove_messages(data.remove_data);
                        }
                    } else if (data.todo == 'load_conversation') {

                        if (data.reload_aside !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === 'groups') {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                            load_aside($(".main .aside > .site_records > .current_record"));
                            $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                        }

                        var load_data = [];
                        load_data[data.identifier_type] = data.identifier;
                        load_conversation(load_data);

                    }
                    if (data.info_box !== undefined) {
                        get_info(data.info_box);
                    }
                    $('.main .page_column[column="'+column+'"] .confirm_box > .content > .btn.cancel').trigger('click');
                } else {
                    if (data.error_message_position === undefined) {
                        if (data.error_message !== undefined) {
                            $('.main .page_column[column="'+column+'"] .confirm_box > .error > .message > span').replace_text(data.error_message);
                            $('.main .page_column[column="'+column+'"] .confirm_box > .error').fadeIn();
                            skip_hide_action = true;
                        }
                    }
                }
            } else {
                console.log('ERROR : ' + data);
            }
            if (element.attr('loader') !== undefined) {
                $(element.attr('loader')).hide();
            }

            element.removeClass('processing');

            if (element.attr('hide_window') !== undefined) {
                $(element.attr('hide_window')).hide();
            }

            if (element.attr('hide_element') !== undefined && !skip_hide_action) {
                $(element.attr('hide_element')).addClass('d-none');
            }

        }) .fail(function(qXHR, textStatus, errorThrown) {
            if (element.attr('loader') !== undefined) {
                $(element.attr('loader')).hide();
            }

            element.removeClass('processing');


            if (element.attr('hide_window') !== undefined) {
                $(element.attr('hide_window')).hide();
            }

            if (element.attr('hide_element') !== undefined) {
                $(element.attr('hide_element')).addClass('d-none');
            }

            console.log('ERROR : ' + errorThrown);
        });
    }
});