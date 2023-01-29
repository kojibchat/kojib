var load_aside_request = null;

$('.main .aside > .site_records > .records > .list').on('scroll', function(e) {
    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 20) {
        if (!$('.main .aside > .site_records > .current_record').hasClass('EndResults') && !$('.main .aside > .site_records > .current_record').hasClass('loading')) {
            $('.main .aside > .site_records > .current_record').addClass('loading');
            load_aside($('.main .aside > .site_records > .current_record'), 1);
        }
    }
});

$("body").on("keyup", ".main .aside > .site_records > .search > input", function(e) {
    if (e.which == 13 || search_on_change_of_input) {
        if ($('.main .aside > .site_records > .current_record').attr('load') !== undefined) {
            var search = $(this).val();
            if (search.length != 0 || $('.main .aside > .site_records > .current_record').attr('null_search') !== undefined) {
                $('.main .aside > .site_records .current_record_search_keyword').val(search);
                $('.main .aside > .site_records .current_record_offset').val('');
                load_aside($('.main .aside > .site_records > .current_record'), 2, 1);
            } else {
                $('.main .aside > .site_records .current_record_filter').val('');
                $('.main .aside > .site_records .current_record_sort_by').val('');
                $('.main .aside > .site_records .current_record_offset').val('');
                $('.main .aside > .site_records .current_record_search_keyword').val('');
                load_aside($('.main .aside > .site_records > .current_record'));
            }
        }
    }
});

$("body").on("click", ".main .aside > .site_records li.select_result", function(e) {

    if ($('.main .aside .selector.select_all').hasClass('d-none')) {
        $(".main .aside > .site_records > .current_record .toggle_checkbox").trigger('click');
    }

    if ($(this).find('div > .selector > input').prop("checked") === true) {
        $(this).find('div > .selector > input').prop("checked", false);
    } else {
        $(this).find('div > .selector > input').prop("checked", true);
    }

    $(this).find('div > .selector > input').trigger('change');
});

$('.main').on('click', '.upload_storage_files', function(e) {
    $('.main .aside > .storage_files_upload_status > .center > .files_attached').addClass('d-none');
    $('.main .aside > .storage_files_upload_status > .center > .files_attached > form > .storage_file_attachments').trigger('click');
});


$('.main .aside > .site_records').on('drop', function(e) {
    e.stopPropagation();
    e.preventDefault();

    if ($('.main .aside > .site_records > .current_record').attr('load') === 'site_user_files') {
        if ($('.main .aside > .site_records > .tools > .tool > span').hasClass('upload_storage_files')) {
            var dropped_files = e.originalEvent.dataTransfer.files;
            var storage_uploader = $('.main .aside > .storage_files_upload_status > .center > .files_attached > form > .storage_file_attachments');
            $('.main .aside > .storage_files_upload_status > .center > .files_attached').addClass('d-none');
            storage_uploader.prop('files', dropped_files);
            storage_uploader.trigger('change');
        }
    }
});

$('.main .aside > .storage_files_upload_status > .center > .files_attached > form > .storage_file_attachments').change(function(e) {
    if ($(this).prop('files').length > 0) {


        $('.main .aside > .storage_files_upload_status').addClass('d-none');
        $('.main .aside > .storage_files_upload_status > .center > .progress > .progress-bar').css('width', '0%');
        $('.main .aside > .storage_files_upload_status > .center > .text > span > .percentage').text('0%');
        $('.main .aside > .storage_files_upload_status > .center > .error > .message > span').html('');
        $('.main .aside > .storage_files_upload_status > .center > .error').hide();
        $('.main .aside > .storage_files_upload_status > .center > .progress').show();
        $('.main .aside > .storage_files_upload_status > .center > .text').show();

        var data = new FormData($(".storage_files_upload_status > .center > .files_attached > form")[0]);
        var file_attachments = $(".storage_files_upload_status > .center > .files_attached > form > .storage_file_attachments").get(0).files;

        for (var i = 0; i < file_attachments.length; i++) {
            data.append("file_attachments[" + i + "]", file_attachments[i]);
        }

        if (user_csrf_token !== null) {
            data.append("csrf_token", user_csrf_token);
        }

        var attached_files_size = 0;
        $(this).each(function() {
            for (var i = 0; i < this.files.length; i++) {
                attached_files_size += this.files[i].size;
            }
        });
        attached_files_size = (attached_files_size / (1024 * 1024)).toFixed(2);

        $('.main .aside > .storage_files_upload_status').removeClass('d-none');

        $.ajax({
            type: 'POST',
            url: api_request_url,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = ((evt.loaded / evt.total) * 100);
                        percentComplete = parseInt(percentComplete);
                        $('.main .aside > .storage_files_upload_status > .center > .progress > .progress-bar').css('width', percentComplete + '%');
                        $('.main .aside > .storage_files_upload_status > .center > .text > span > .percentage').text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            data: data,
            cache: false,
            contentType: false,
            processData: false,
            async: true,
            beforeSend: function() {},
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                if (data.success) {
                    if (data.reload !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === data.reload) {
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                        $(".main .aside > .site_records > .current_record > .title").trigger('click');
                    }
                    $('.main .aside > .storage_files_upload_status').addClass('d-none');
                } else {
                    $('.main .aside > .storage_files_upload_status > .center > .progress').hide();
                    $('.main .aside > .storage_files_upload_status > .center > .text').hide();
                    $('.main .aside > .storage_files_upload_status > .center > .error > .message > span').replace_text(data.error_message);
                    $('.main .aside > .storage_files_upload_status > .center > .error').fadeIn();

                    setTimeout(function() {
                        $('.main .aside > .storage_files_upload_status').addClass('d-none');
                    }, 3000);
                }
            } else {
                console.log('ERROR : ' + data);
                $('.main .aside > .storage_files_upload_status').addClass('d-none');
            }
        }).fail(function(qXHR, textStatus, errorThrown) {
            $('.main .aside > .storage_files_upload_status').addClass('d-none');
            console.log('ERROR : ' + errorThrown);
        });
    }
});

$('.main').on('click', '.load_aside', function(e) {
    open_module('.site_records', '.main .aside', true);
    open_column('first');
    load_aside($(this));
    $('.main .aside > .mini_audio_player').removeClass('d-none');
});

function load_aside(load, append = 0, skiptitle = 0) {
    if (load.attr('load') !== undefined) {
        var offset = filter = sortby = search = 0;
        var preloader_disabled = false;

        if (load.attr('disable_preloader') != undefined) {
            preloader_disabled = true;
            load.removeAttr('disable_preloader');
        }

        $('.main .aside > .site_records > .records > .zero_results').addClass('d-none');
        $('.main .aside > .site_records > .records > .on_error').addClass('d-none');
        $('.main .aside > .site_records > .current_record').removeClass('EndResults');
        $('.main .aside > .site_records > .current_record').addClass('loading');

        if (append == 0) {

            if (load.attr('sort') == undefined) {
                if (load.attr('filter') !== undefined) {

                    if (load.attr('skip_filter_title') === undefined) {
                        $('.main .aside > .site_records > .current_record > .title > div > .filter').replace_text('[' + load.text() + ']');
                    } else {
                        $('.main .aside > .site_records > .current_record > .title > div > .filter').replace_text('');
                    }

                    filter = load.attr('filter');
                } else {
                    $('.main .aside > .site_records > .current_record > .title > div > .filter').replace_text('');
                }
            }

            if (load.attr('sort') !== undefined) {
                $('.main .aside > .site_records > .current_record > .options > div.sort > span > span').replace_text(load.text());
                filter = $('.main .aside > .site_records .current_record_filter').val();
                sortby = load.attr('sort');
            } else {
                $('.main .aside > .site_records > .current_record > .options > div.sort > span > span').replace_text(language_string('sort'));
            }

            if (load.attr('search') !== undefined) {
                search = load.attr('search');
            }

            if (!preloader_disabled) {
                $('.main .aside > .site_records > .records > .list').html('').hide();
            }

            if (skiptitle == 0 && !preloader_disabled) {
                $('.main .aside > .site_records > .current_record > .title > div > .text').replace_text(language_string('loading'));
            }
            if (!preloader_disabled) {
                $('.main .aside > .site_records > .current_record > .options > div > .dropdown_list > ul').html('');
                $('.main .aside > .site_records > .current_record > .options > div').addClass('d-none');
                $('.main .aside > .site_records > .tools > .tool').addClass('d-none');
            }

            if (!preloader_disabled) {
                $('.main .aside > .site_records > .current_record > .selector').addClass('d-none');
            }

            $('.main .aside > .site_records > .current_record > .selector > input').prop('checked', false);
            $('.main .aside > .site_records > .confirm_box > .error').hide();
            $('.main .aside > .site_records > .confirm_box').addClass('d-none');
            $('.main .aside > .site_records > .current_record').removeClass('d-none');
            $('.main .aside > .site_records > .records').removeClass('blur');

            if ($('.main .aside > .site_records > .records > .loader > ul > li').length === 0) {
                $('.main .aside > .site_records > .records > .loader > ul').html(loader_content());
            }

            $('.main .aside > .site_records > .records > .loader').removeClass('error');

            if (!preloader_disabled) {
                $('.main .aside > .site_records > .records > .loader').show();
            }

            $('.main .aside > .site_records > .search > input').val('');


            $('.main .aside > .site_records .current_record_filter').val(filter);
            $('.main .aside > .site_records .current_record_sort_by').val(sortby);
            $('.main .aside > .site_records .current_record_offset').val(offset);
            $('.main .aside > .site_records .current_record_search_keyword').val('');

            $('.main .aside > .site_records > .current_record').attr('load', load.attr('load'));
            $('.main .aside > .site_records > .current_record').removeAttr('null_search');

            $('.main .aside > .site_records .record_info > .data_attributes').html('');

            var append_data = load.data();
            $.each(append_data, function(index, data_item) {
                if ($('.main .aside > .site_records .record_info > .data_attributes > span').length === 0) {
                    $('.main .aside > .site_records .record_info > .data_attributes').html('<span>Append Data</span>');
                }
                var data_index = 'data-' + index;
                $('.main .aside > .site_records .record_info > .data_attributes > span').attr(data_index, append_data[index]);
            });

        } else {
            offset = $('.main .aside > .site_records .current_record_offset').val();
            filter = $('.main .aside > .site_records .current_record_filter').val();
            sortby = $('.main .aside > .site_records .current_record_sort_by').val();
            search = $('.main .aside > .site_records .current_record_search_keyword').val();

            if (append == 2) {
                $('.main .aside > .site_records > .records > .loader').show();
            }
        }


        var data = {
            load: load.attr('load'),
            filter: filter,
            offset: offset,
            sortby: sortby,
            search: search,
        };

        if (load.attr('check_conversation_loaded') != undefined) {
            if ($(".main .chatbox").attr('group_id') !== undefined && $(".main .chatbox").attr('group_id') !== 'all') {
                data['conversation_loaded'] = true;
            } else if ($(".main .chatbox").attr('user_id') !== undefined && $(".main .chatbox").attr('user_id') !== 'all') {
                data['conversation_loaded'] = true;
            }
        }

        if (user_csrf_token !== null) {
            data["csrf_token"] = user_csrf_token;
        }

        if ($('.main .aside > .site_records .record_info > .data_attributes > span').length > 0) {
            data = $.extend(data, $('.main .aside > .site_records .record_info > .data_attributes > span').data());
        }

        load_aside_request = $.ajax({
            type: 'POST',
            url: api_request_url,
            data: data,
            async: true,
            beforeSend: function() {
                if (load_aside_request != null) {
                    load_aside_request.abort();
                    load_aside_request = null;
                }
            },
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                var list = filter = sort = '';
                var totalitems = totalfilters = totalsortby = 0;
                var content = data.content;
                var alloptions = data.options;
                var filters = data.filters;
                var sortby = data.sortby;
                var todo = data.todo;

                if (Object.keys(data).length > 0) {
                    if (data.content !== undefined) {
                        totalitems = Object.keys(data.content).length;
                    }

                    if (data.filters !== undefined) {
                        totalfilters = Object.keys(data.filters).length;
                    }

                    if (data.sortby !== undefined) {
                        totalsortby = Object.keys(data.sortby).length;
                    }
                    if (data.loaded !== undefined) {
                        if (skiptitle == 0) {
                            $('.main .aside > .site_records > .current_record > .title > div > .text').replace_text(data.loaded.title);
                        }
                        if (data.loaded.offset !== undefined) {
                            $('.main .aside > .site_records .current_record_offset').val(data.loaded.offset);
                        }

                        if (data.loaded.null_search !== undefined) {
                            $('.main .aside > .site_records > .current_record').attr('null_search', true);
                        }

                        if (data.multiple_select !== undefined && data.multiple_select.title !== undefined) {
                            //$('.main .aside > .site_records > .current_record > .selector').removeClass('d-none');
                            $('.main .aside > .site_records > .current_record > .options > div.toggle_checkbox').removeClass('d-none');
                        }
                    }

                    if (append === 0 && data.todo !== undefined) {
                        $('.main .aside > .site_records > .tools > .todo').html('<span><i class="iconic_plus"></i></span>');
                        $('.main .aside > .site_records > .tools > .todo > span').addClass(data.todo.class);
                        $('.main .aside > .site_records > .tools > .todo > span').addClass("hide_tooltip_on_click");
                        var todoattrs = data.todo.attributes;
                        $('.main .aside > .site_records > .tools, .main .aside > .site_records > .tools > .todo').removeClass('d-none');
                        $('.main .aside > .site_records > .tools > .todo > span > i').attr('title', data.todo.title);
                        $('.main .aside > .site_records > .tools > .todo > span > i').attr('data-bs-toggle', 'tooltip');
                        $('.main .aside > .site_records > .tools > .todo > span > i').attr('data-bs-placement', 'left');
                        if (todoattrs !== undefined) {
                            $.each(todoattrs, function(key, val) {
                                $('.main .aside > .site_records > .tools > .todo > span').attr(key, val);
                            });
                        }
                    }
                }

                if (!preloader_disabled) {
                    if (append === 0 && totalsortby > 0) {
                        $.each(sortby, function(key, val) {
                            var attributes = sortby[key].attributes;
                            sort = sort + '<li ';
                            $.each(attributes, function(attrkey, attrval) {
                                sort = sort + attrkey + '="' + attributes[attrkey] + '" ';
                            });
                            sort = sort + 'class="' + sortby[key].class + '">' + sortby[key].sortby + '</li>';
                        });
                        $('.main .aside > .site_records > .current_record > .options > div.sort .dropdown_list > ul').html(sort);
                        $('.main .aside > .site_records > .current_record > .options > div.sort').addClass('right');
                        $('.main .aside > .site_records > .current_record > .options > div.sort').removeClass('d-none');
                    } else if (append == 0) {
                        $('.main .aside > .site_records > .current_record > .options > div.sort').removeClass('right');
                        $('.main .aside > .site_records > .current_record > .options > div.sort').addClass('d-none');
                    }
                }


                if (append == 0 && totalfilters > 0) {
                    $.each(filters, function(key, val) {
                        var attributes = filters[key].attributes;
                        filter = filter + '<li ';
                        $.each(attributes, function(attrkey, attrval) {
                            filter = filter + attrkey + '="' + attributes[attrkey] + '" ';
                        });
                        filter = filter + 'class="' + filters[key].class + '">' + filters[key].filter + '</li>';
                    });
                    $('.main .aside > .site_records > .current_record > .title > div > .dropdown_list > ul').html(filter);
                    $('.main .aside > .site_records > .current_record > .title > div').addClass('dropdown_button');
                } else if (append == 0) {
                    $('.main .aside > .site_records > .current_record > .title > div > .dropdown_list > ul').html('');
                    $('.main .aside > .site_records > .current_record > .title > div').removeClass('dropdown_button');
                }

                if (data.multiple_select !== undefined && data.multiple_select.title !== undefined) {

                    var icon_class = 'iconic_close';

                    if (data.multiple_select.icon !== undefined) {
                        icon_class = data.multiple_select.icon;
                    }

                    $('.main .aside > .site_records > .tools > .multiple_selection').html('<span><i class="' + icon_class + '"></i><span>');
                    $('.main .aside > .site_records > .tools > .multiple_selection > span > i').attr('data-bs-toggle', 'tooltip');
                    $('.main .aside > .site_records > .tools > .multiple_selection > span > i').attr('data-bs-placement', 'left');
                    $('.main .aside > .site_records > .tools > .multiple_selection > span > i').attr('title', data.multiple_select.title);
                    if (data.multiple_select.attributes !== undefined) {
                        $.each(data.multiple_select.attributes, function(attrkey, attrval) {
                            $('.main .aside > .site_records > .tools > .multiple_selection > span').attr(attrkey, attrval);
                        });
                    }
                }

                if (totalitems > 0) {
                    $.each(content, function(key, val) {
                        var item_attributes = '';

                        if (content[key].attributes !== undefined) {
                            $.each(content[key].attributes, function(item_attr_key, item_attr_val) {
                                item_attributes = item_attributes + item_attr_key + '="' + item_attr_val + '" ';
                            });
                        }

                        list = list + '<li class="site_record_item ' + content[key].class + '" ' + item_attributes + '> <div>';

                        if (data.loaded !== undefined) {
                            if (data.multiple_select !== undefined && data.multiple_select.title !== undefined) {
                                list = list + '<label class="selector prevent_default select_item d-none">';
                                list = list + '<input type="checkbox" name="' + data.multiple_select.attributes.multi_select + '[]" value="' + content[key].identifier + '"/>';
                                list = list + '<span class="checkmark"></span>';
                                list = list + '</label>';
                            }
                        }

                        list = list + '<div class="left">';
                        if (content[key].alphaicon != undefined) {
                            list = list + '<span class="alphaicon">';

                            if (content[key].title != undefined && content[key].title != null) {
                                list = list + '<i>' + content[key].title.slice(0, 1) + '</i>';
                            }

                            list = list + '</span>';
                        } else {
                            list = list + '<img onload="on_image_load(this)" src="' + content[key].image + '">';

                            if (content[key].online_status !== undefined) {
                                list = list + '<span class="online_status ' + content[key].online_status + '"></span>';
                            }
                        }
                        list = list + '</div>';

                        list = list + '<div class="center">';

                        list = list + '<span class="title">';
                        list = list + '<span class="text" data-bs-toggle="tooltip" title="' + content[key].title + '">' + content[key].title + '</span>';

                        list = list + '<span class="unread">';
                        if (content[key].unread !== 0) {
                            list = list + '<span unread="' + content[key].unread + '">' + content[key].unread + ' ' + language_string('new') + '</span>';
                        }
                        list = list + '</span>';

                        list = list + '</span>';


                        list = list + '<span class="subtitle">';
                        list = list + content[key].subtitle;
                        if (content[key].icon !== 0) {
                            list = list + '<i class="icon ' + content[key].icon + '" data-bs-toggle="tooltip" title="' + content[key].icon_text + '"></i>';
                        }
                        list = list + '</span>';
                        list = list + '</div>';

                        list = list + '<div class="right">';
                        list = list + '<span class="options prevent_default">';
                        if (alloptions != undefined && alloptions[key] !== undefined && alloptions[key].length != 0) {
                            list = list + '<i class="iconic_three-dots"></i>';

                            if (content[key].attributes !== undefined && content[key].attributes.stopPropagation !== undefined) {
                                list = list + '<span><ul class="prevent_default">';
                            } else {
                                list = list + '<span><ul>';
                            }

                            var options = alloptions[key];
                            $.each(options, function(optkey, optval) {
                                var attributes = options[optkey].attributes;
                                list = list + '<li ';
                                $.each(attributes, function(attrkey, attrval) {
                                    list = list + attrkey + '="' + attributes[attrkey] + '" ';
                                });
                                list = list + 'class="' + options[optkey].class + '">' + options[optkey].option + '</li>';
                            });
                            list = list + '</ul></span>';
                        }
                        list = list + '</span></div>';

                        list = list + '</div></li>';
                    });

                    if (append === 1) {
                        $('.main .aside > .site_records > .records > .list').append(list).show();
                    } else {

                        if (!preloader_disabled) {
                            $('.main .aside > .site_records > .records > .list').css('opacity', 0);
                        }

                        $('.main .aside > .site_records > .records > .list').html(list).show();
                    }

                    if (!$('.main .aside .selector.select_all').hasClass('d-none')) {
                        $('.main .aside .selector').removeClass('d-none');
                    }

                } else {
                    if (append == 1) {
                        $('.main .aside > .site_records > .current_record').addClass('EndResults');
                    } else {
                        $('.main .aside > .site_records > .current_record > .selector').addClass('d-none');
                        $('.main .aside > .site_records > .records > .zero_results').removeClass('d-none');
                        $('.main .aside > .site_records > .records > .list').hide();
                    }
                }

                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                $('.main .aside > .site_records > .current_record').removeClass('loading');
                $('.main .aside > .site_records > .records > .loader').removeClass('error').hide();

                if (!preloader_disabled) {
                    $('.main .aside > .site_records > .records > .list').animate({
                        opacity: 1
                    }, 0);
                }

            } else {
                console.log('ERROR : ' + data);

                if (!preloader_disabled && append !== 1) {
                    $('.main .aside > .site_records > .records > .list').html('');
                    $('.main .aside > .site_records > .current_record').removeClass('loading');
                    $('.main .aside > .site_records > .current_record > .title > div > .text').replace_text(language_string('error'));
                    $('.main .aside > .site_records > .records > .on_error').removeClass('d-none');
                    $('.main .aside > .site_records > .records > .loader').addClass('error').show();
                }
            }
        }).fail(function(qXHR, textStatus, errorThrown) {
            if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {

                if (!preloader_disabled && append !== 1) {
                    $('.main .aside > .site_records > .records > .list').html('');
                    $('.main .aside > .site_records > .current_record').removeClass('loading');
                    $('.main .aside > .site_records > .records > .on_error').removeClass('d-none');
                    $('.main .aside > .site_records > .current_record > .title > div > .text').replace_text(language_string('error'));
                    $('.main .aside > .site_records > .records > .loader').addClass('error').show();
                }
            }
        });
    }
}


$('body').on('click', '.main .aside > .site_records > .current_record > .selector.select_all > input', function(e) {
    $('.main .aside > .site_records > .records > .list > li > div > .selector.select_item > input').prop('checked', this.checked).change();
});


$('body').on('change', '.main .aside > .site_records > .records > .list > li > div > .selector.select_item > input', function(e) {
    if ($(".main .aside > .site_records > .records > .list > li > div > .selector.select_item > input:checked").length > 0) {

        if ($('.main .aside > .site_records > .tools').hasClass('d-none')) {
            if ($('.main .page_column[column="first"] .confirm_box').hasClass('d-none')) {
                $('.main .aside > .site_records > .tools').removeClass('d-none');
            }
        }

        $('.main .aside > .site_records > .tools > .multiple_selection').removeClass('d-none');
    } else {
        $('.main .aside > .site_records > .tools > .multiple_selection').addClass('d-none');
    }
});

$('body').on('click', ".main .aside > .site_records > .records > .list > li", function(event) {
    if (!$(event.target).hasClass('prevent_default') && !$(event.target).parent().hasClass('prevent_default')) {

        if (!$(this).find(".options > span").is(":visible")) {
            $(".main .aside > .site_records > .records > .list > li > div > .right > .options > span").hide();
        }

        if ($(this).attr('stopPropagation') === undefined) {
            $(this).find(".options").trigger('click');
        }
    }
});


$('body').on('click', ".main .aside > .site_records > .current_record .toggle_checkbox", function(e) {

    if ($('.main .aside > .site_records > .records > .list > li > div > .selector').length > 0) {
        $('.main .aside > .site_records .selector').toggleClass('d-none');
    }

    if ($('.main .aside .selector.select_all').hasClass('d-none')) {
        $('.main .aside .selector > input[type="checkbox"]').prop('checked', false);
        $('.main .aside > .site_records > .tools > .multiple_selection').addClass('d-none');
    }

});

$('body').on('click', ".main .aside > .site_records > .records > .list > li > div > .right > .options", function(e) {
    if ($(this).find("span").is(":visible")) {
        $(".main .aside > .site_records > .records > .list > li > div > .right > .options > span").hide();
    } else {
        $(".main .aside > .site_records > .records > .list > li > div > .right > .options > span").hide();
        $(this).find("span").css('display', 'block');
    }
});


$("body").on('click', '.main .aside > .site_records > .current_record > .title', function(e) {
    if (!$('.main .aside > .site_records > .current_record').hasClass('loading') && !$(this).find('div').hasClass('dropdown_button') || $(this).attr('disable_preloader') !== undefined) {

        $('.main .aside > .site_records .record_info > .refresh_current_record').html('<span>Refresh</span>');
        $('.main .aside > .site_records .record_info > .refresh_current_record > span').addClass('load_aside');
        $('.main .aside > .site_records .record_info > .refresh_current_record > span').attr('load', $('.main .aside > .site_records > .current_record').attr('load'));

        if ($(this).attr('filter_data') !== undefined) {
            $('.main .aside > .site_records .record_info > .refresh_current_record > span').attr('filter', 'pending_approval');
            $('.main .aside > .site_records .record_info > .refresh_current_record > span').attr('skip_filter_title', true);
        }

        $(this).removeAttr('filter_data');

        if ($('.main .aside > .site_records .record_info > .data_attributes > span').length > 0) {
            var append_data = $('.main .aside > .site_records .record_info > .data_attributes > span').data();
            $.each(append_data, function(index, data_item) {
                var data_index = 'data-' + index;
                $('.main .aside > .site_records .record_info > .refresh_current_record > span').attr(data_index, append_data[index]);
            });
        }

        if ($(this).attr('disable_preloader') !== undefined) {
            $('.main .aside > .site_records .record_info > .refresh_current_record > span').attr('disable_preloader', true);
        }

        $('.main .aside > .site_records .record_info > .refresh_current_record > span').trigger('click');

    }
});