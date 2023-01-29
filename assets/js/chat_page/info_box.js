var get_info_request = null;
var load_info_records_request = null;

$('body').on('click', '.get_info', function(event) {
    if (!$(event.target).hasClass('prevent_default') && !$(event.target).parent().hasClass('prevent_default') || $(this).hasClass('force_request')) {

        var userid = groupid = 0;
        var data = {
            load: 'site_user'
        };

        if ($(this).attr('user_id') !== undefined) {
            data['user_id'] = $(this).attr('user_id');
        } else if ($(this).attr('group_id') !== undefined) {
            data['group_id'] = $(this).attr('group_id');
        } else if ($(this).attr('auto_find') !== undefined) {
            if ($(".main .chatbox").attr('group_id') !== undefined) {
                data['load'] = 'site_user';
                data['group_id'] = $(".main .chatbox").attr('group_id');
            } else if ($(".main .chatbox").attr('user_id') !== undefined) {
                data['user_id'] = $(".main .chatbox").attr('user_id');
            } else {
                data['user_id'] = 1;
            }
        }

        if ($(window).width() < 767.98) {
            $('.main .chat_page_container').removeClass('show_navigation');
        }

        data['open_column'] = true;

        data = $.extend(data, $(this).data());
        get_info(data);
    }
});

$('.main .close_info_panel').on('click', function(e) {
    open_column('first', true);
    $('.main .middle').removeClass('col-lg-5');
    $('.main .middle').removeClass('col-lg-6');
    $('.main .middle').addClass('col-lg-9');
    $('.main .info_panel').addClass('d-none');
    $('.main .formbox').addClass('d-none');
});


function get_info(data) {

    data['load'] = 'site_user';

    if (data['group_id'] !== undefined) {
        data['load'] = 'group';
    }

    if (data['open_column'] !== undefined && data['open_column'] === true) {

        open_column('fourth');

        $('.main .page_column[column="fourth"] .confirm_box > .content > .btn.cancel').trigger('click');

        $('.main .middle').removeClass('col-lg-9');
        $('.main .middle').addClass('col-lg-6');

        $('.main .formbox').addClass('d-none');
        $('.main .info_panel').removeClass('d-none');
    }

    $('.main .info_panel > .loader').removeClass('error');
    $(".main .info_panel > .loader > div > .error_text > .title").replace_text(language_string('error'));
    $(".main .info_panel > .loader > div > .error_text > .subtitle").replace_text(language_string('error_message'));
    $(".main .info_panel > .info_box > .img > .online_status").hide();
    $('.main .info_panel > .loader').show();

    if (user_csrf_token !== null) {
        data["csrf_token"] = user_csrf_token;
    }

    get_info_request = $.ajax({
        type: 'POST',
        url: api_request_url,
        data: data,
        async: true,
        beforeSend: function() {
            if (load_info_records_request != null) {
                load_info_records_request.abort();
                load_info_records_request = null;
            }
            if (get_info_request != null) {
                get_info_request.abort();
                get_info_request = null;
            }
        },
        success: function(data) {}
    }).done(function(data) {
        if (isJSON(data)) {
            data = $.parseJSON(data);

            var images = options = statistics = contents = '';

            if (Object.keys(data).length > 0) {

                if (data.error !== undefined) {

                    $('.main .info_panel > .loader').addClass('error');

                    if (data.error.title !== undefined) {
                        $(".main .info_panel > .loader > div > .error_text > .title").replace_text(data.error.title);
                    }

                    $(".main .info_panel > .loader > div > .error_text > .subtitle").replace_text(data.error.message);

                } else {
                    if (data.loaded !== undefined) {
                        $(".main .info_panel > .info_box > .heading").replace_text(data.loaded.heading);
                        $(".main .info_panel > .info_box > .subheading").replace_text(data.loaded.subheading);
                        $(".main .info_panel > .coverpic > .img").html('<img src="'+data.loaded.cover_pic+'"/>');
                        $(".main .info_panel > .info_box > .img > img").attr('src', data.loaded.image);

                        if (data.loaded.online_status !== undefined) {

                            $(".main .info_panel > .info_box > .img > .online_status > span").removeClass('online idle offline');
                            $(".main .info_panel > .info_box > .img > .online_status > span").addClass(data.loaded.online_status);
                            $(".main .info_panel > .info_box > .img > .online_status").show();
                        }
                    }

                    $('.main .info_panel > .controls > div > span,.main .info_panel > .controls > div > div').addClass('d-none');
                    $('.main .info_panel > .controls > div > span').html('');

                    if (data.button !== undefined) {

                        $('.main .info_panel > .controls > div > span').removeAttr('class');
                        $('.main .info_panel > .controls > div > span').addClass(data.button.class);
                        $('.main .info_panel > .controls > div > span').html('<span></span>');


                        $.each(data.button.attributes, function(attrkey, attrval) {
                            $('.main .info_panel > .controls > div > span > span').attr(attrkey, attrval);
                        });

                        $('.main .info_panel > .controls > div > span > span').replace_text(data.button.title);
                        $('.main .info_panel > .controls > div > span').removeClass('d-none');

                    }

                    if (data.options !== undefined) {

                        $('.main .info_panel > .controls > div > .options').removeClass('d-none');

                        $.each(data.options, function(key, val) {
                            options = options+'<li class="'+data.options[key].class+'" ';
                            $.each(data.options[key].attributes, function(attrkey, attrval) {
                                options = options+attrkey+'="'+data.options[key].attributes[attrkey]+'" ';
                            });

                            if (data.options[key].title !== undefined) {
                                options = options+'>'+data.options[key].title+'</li>';
                            } else {
                                options = options+'>'+data.options[key].option+'</li>';
                            }
                        });
                    }

                    $('.main .info_panel > .controls > div > .options > .dropdown_list > ul').html(options);

                    if (data.statistics !== undefined) {
                        $.each(data.statistics, function(key, val) {
                            statistics = statistics+'<div>';
                            statistics = statistics+'<span class="value">'+data.statistics[key].value+'</span>';
                            statistics = statistics+'<span>'+data.statistics[key].title+'</span>';
                            statistics = statistics+'</div>';
                        });
                        $('.main .info_panel > .statistics').show();
                    } else {
                        $('.main .info_panel > .statistics').hide();
                    }

                    $('.main .info_panel > .statistics > div').html(statistics);

                    if (data.content !== undefined) {
                        $.each(data.content, function(key, val) {
                            if (data.content[key].field !== undefined) {
                                contents = contents+'<div class="field">';

                                if (data.content[key].field.title !== undefined) {
                                    contents = contents+'<span class="title">'+data.content[key].field.title+'</span>';
                                }

                                if (data.content[key].field.images !== undefined) {

                                    var image_class = "default";

                                    images = data.content[key].field.images;

                                    if (data.content[key].field.class !== undefined) {
                                        image_class = data.content[key].field.class;
                                    }

                                    contents = contents+'<div class="icons '+image_class+'"><ul>';

                                    $.each(images, function(img_key, image) {

                                        contents = contents+'<li';

                                        $.each(image.attributes, function(img_attribute_key, img_attribute_value) {
                                            contents = contents+' '+img_attribute_key+'="'+img_attribute_value+'"';
                                        });

                                        contents = contents+'><img title="'+image.title+'" data-bs-toggle="tooltip" src="'+image.image+'"/>';
                                        contents = contents+'</li>';
                                    });

                                    contents = contents+'</ul></div>';

                                } else if (data.content[key].field.multiple_records !== undefined) {

                                    var records_identifier = 'multiple_records';
                                    var multi_records = data.content[key].field.multiple_records;

                                    if (multi_records.identifier !== undefined) {
                                        records_identifier = multi_records.identifier;
                                    }

                                    contents = contents+'<div class="info_records '+records_identifier+'">';

                                    if (multi_records.dropdown !== undefined) {
                                        contents = contents+'<div class="record_selection">';
                                        contents = contents+'<div class="dropdown_button">';
                                        contents = contents+'<span class="text">'+language_string('loading')+'</span>';
                                        contents = contents+'<div class="dropdown_list">';
                                        contents = contents+'<ul>';

                                        $.each(multi_records.dropdown, function(index, dropdown_item) {

                                            var dropdown_attributes = '';

                                            if (dropdown_item.attributes !== undefined) {

                                                $.each(dropdown_item.attributes, function(attr_index, attr_value) {
                                                    dropdown_attributes += attr_index+'="'+attr_value+'" ';
                                                });
                                            }


                                            contents = contents+'<li '+dropdown_attributes+' load="'+dropdown_item.load+'" class="hide_onClick load_info_records" ';
                                            contents = contents+'records_block_class="'+records_identifier+'">'+dropdown_item.title+'</li>';
                                        });

                                        contents = contents+'</ul>';
                                        contents = contents+'</div>';
                                        contents = contents+'</div>';
                                        contents = contents+'</div>';
                                    }

                                    contents = contents+'<div class="contents">';
                                    contents = contents+'<div>';
                                    contents = contents+'<ul>';

                                    contents = contents+'</ul>';
                                    contents = contents+'</div>';
                                    contents = contents+'</div>';

                                    contents = contents+'<div class="no_results_found d-none">';
                                    contents = contents+'<span class="image">';
                                    contents = contents+'<span></span>';
                                    contents = contents+'</span>';
                                    contents = contents+'</div>';

                                    contents = contents+'<div class="current_records d-none">';
                                    contents = contents+'<span class="loaded"></span>';
                                    contents = contents+'<span class="data_attributes"></span>';
                                    contents = contents+'<span class="offset"></span>';
                                    contents = contents+'</div>';

                                    contents = contents+'<div class="load_more d-none">';
                                    contents = contents+'<div class="load_info_records" append=true records_block_class="'+records_identifier+'">';
                                    contents = contents+'<span>'+language_string('load_more')+'</span></div>';
                                    contents = contents+'</div>';

                                    contents = contents+'</div>';
                                } else if (data.content[key].field.value !== undefined) {
                                    contents = contents+'<span class="value">'+data.content[key].field.value+'</span>';
                                }

                                contents = contents+'</div>';
                            }
                        });
                    }

                    $('.main .info_panel > .content > .fields').html(contents);

                    if ($('.main .info_panel .info_records > .record_selection > div > .dropdown_list > ul > li').length > 0) {
                        $('.main .info_panel .info_records > .record_selection > div > .dropdown_list > ul > li:first-child').trigger('click');
                        $('.main .info_panel .info_records > .record_selection > div > .dropdown_list').hide();
                    }

                    //$("[data-bs-toggle=tooltip]").tooltip();

                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    })

                    $('.main .info_panel > .loader').hide();

                }
            }

        } else {
            console.log('ERROR : ' + data);
            $(".main .info_panel > .info_box > .heading").replace_text(language_string('error'));
            $('.main .info_panel > .loader').addClass('error');
        }

    }).fail(function(qXHR, textStatus, errorThrown) {
        if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
            $('.main .info_panel > .loader').addClass('error');
            $(".main .info_panel > .info_box > .heading").replace_text(language_string('error'));
        }
    });
}

$('.main').on('click', '.load_info_records', function(e) {

    var load = $(this);

    if (load.attr('records_block_class') !== undefined) {
        var records_block = $('.main .info_panel > .content > .fields > .field > .info_records.'+load.attr('records_block_class'));
        var offset = 0;
        var load_records = '';

        if (load.attr('load') !== undefined) {
            load_records = load.attr('load');
        }

        records_block.find('.load_more').addClass('d-none');

        if (load.attr('append') !== undefined) {
            load_records = $.trim(records_block.find('.current_records > .loaded').text());
            offset = $.trim(records_block.find('.current_records > .offset').text());
        } else {
            records_block.find('.no_results_found').addClass('d-none');
            records_block.find('.current_records > .loaded').text(load_records);
            records_block.find('.contents > div > ul').html('');
            records_block.find('.record_selection > div > .text').replace_text(language_string('loading'));

            var append_data = load.data();

            $.each(append_data, function(index, data_item) {
                if (records_block.find('.current_records > .data_attributes > span').length === 0) {
                    records_block.find('.current_records > .data_attributes').html('<span>Append Data</span>');
                }
                var data_index = 'data-'+index;
                records_block.find('.current_records > .data_attributes > span').attr(data_index, append_data[index]);
            });
        }

        var data = {
            load: load_records,
            offset: offset,
        };

        data = $.extend(data, records_block.find('.current_records > .data_attributes > span').data());

        if (user_csrf_token !== null) {
            data["csrf_token"] = user_csrf_token;
        }

        load_info_records_request = $.ajax({
            type: 'POST',
            url: api_request_url,
            data: data,
            async: true,
            beforeSend: function() {
                if (load_info_records_request != null) {
                    load_info_records_request.abort();
                    load_info_records_request = null;
                }
            },
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                var contents = '';

                if (data.loaded !== undefined) {
                    if (data.loaded.format !== undefined) {

                        records_block.find('.contents').removeClass('grid_format list_format');
                        records_block.find('.contents').addClass(data.loaded.format+'_format');

                        if (data.loaded.offset !== undefined) {
                            records_block.find('.current_records > .offset').text(data.loaded.offset);
                        }

                        if (load.attr('append') === undefined) {
                            if (data.loaded.title !== undefined) {
                                records_block.find('.record_selection > div > .text').replace_text(data.loaded.title);
                            }
                        }

                        if (data.loaded.load_more !== undefined) {
                            records_block.find('.load_more').removeClass('d-none');
                        }

                        if (data.content !== undefined) {
                            if (data.loaded.format === 'grid') {
                                $.each(data.content, function(index, item) {
                                    var attributes = '';
                                    if (item.attributes !== undefined) {
                                        $.each(item.attributes, function(attr_index, attr_value) {
                                            attributes += attr_index+'="'+attr_value+'" ';
                                        });
                                    }

                                    contents = contents+'<li>';
                                    contents = contents+'<span '+attributes+'>';
                                    contents = contents+'<img src="'+item.image+'"/>';
                                    contents = contents+'</span>';
                                    contents = contents+'</li>';
                                });
                            } else if (data.loaded.format === 'list') {
                                $.each(data.content, function(index, item) {
                                    var attributes = '';
                                    if (item.attributes !== undefined) {
                                        $.each(item.attributes, function(attr_index, attr_value) {
                                            attributes += attr_index+'="'+attr_value+'" ';
                                        });
                                    }

                                    contents = contents+'<li '+attributes+'>';
                                    contents = contents+'<span class="left">';
                                    contents = contents+'<span class="image">';
                                    contents = contents+'<img src="'+item.image+'" onerror="on_error_img(this,2)"/>';
                                    contents = contents+'</span>';
                                    contents = contents+'</span>';
                                    contents = contents+'<span class="right">';

                                    if (item.heading !== undefined) {
                                        contents = contents+'<span class="heading">'+item.heading+'</span>';
                                    }

                                    if (item.description !== undefined) {
                                        contents = contents+'<span class="description">'+item.description+'</span>';
                                    }

                                    if (item.footer !== undefined) {
                                        contents = contents+'<span class="footer">'+item.footer+'</span>';
                                    }

                                    contents = contents+'</span>';
                                    contents = contents+'</li>';
                                });
                            }
                        }

                        if (load.attr('append') !== undefined) {
                            records_block.find('.contents > div > ul').append(contents);
                        } else {

                            if (contents.length === 0) {
                                records_block.find('.no_results_found').removeClass('d-none');
                            }

                            records_block.find('.contents > div > ul').html(contents);
                        }
                    }
                }

            } else {
                console.log('ERROR : ' + data);
            }
        }) .fail(function(qXHR, textStatus, errorThrown) {
            if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
                console.log('ERROR : ' + errorThrown);
            }
        });
    }
});