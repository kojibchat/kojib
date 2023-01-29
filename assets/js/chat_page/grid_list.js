var grid_list_request = {
    'gifs': null,
    'stickers': null,
    'emojis': null
};


$("body").on("keyup", ".grid_list > div > .search", function(e) {
    if (e.which == 13) {
        var module = $(this).parent();
        grid_list(module);
    }
});

$("body").on("click", ".load_grid_list", function(e) {
    var load_list = true;
    var module = $('.grid_list > div.'+$(this).attr('load'));

    if (load_list) {

        if ($(this).attr('reload') !== undefined || module.find('.results > div > ul > li').length == 0) {
            module.find('.data_attributes').html('<span></span>');
            $.each($(this).data(), function (name, value) {
                name = 'data-'+name;
                module.find('.data_attributes > span').attr(name, value);
            });

            grid_list(module);
        }

        $('.grid_list > div').addClass('d-none');
        module.removeClass('d-none');
    }

    if ($(window).width() > 767.98) {
        $('#message_editor').summernote('focus');
    }

});


$('.main .chatbox > .footer > .grid_list > div > .results').on('scroll', function(e) {

    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight-20) {
        var module = $(this).parent();

        if (module.attr('offset') !== undefined && module.attr('offset') !== 0) {
            if (!module.find('results').hasClass('loading')) {
                grid_list(module, true);
                module.find('.results').addClass('loading');
            }
        }

    }
});


function grid_list(module, append) {

    var search = null;
    var list = subtabs = '';


    var offset = 0;

    search = module.find('.search > div > input').val();

    var loader_content = '';
    for (let i = 0; i < 16; i++) {
        loader_content = loader_content+'<li class="item_loading"><span class="loader"><span>';
        loader_content = loader_content+'<span></span>';
        loader_content = loader_content+'</span></li>';
    }

    if (append === undefined) {
        module.find('.results > div > ul').removeAttr('class').html(loader_content);
        module.find(".results").scrollTop(0);
        module.attr('offset', 0);
    } else {
        offset = module.attr('offset');

        if (offset == 'endofresults') {
            return false;
        }
    }

    module.find('.results').addClass('loading');

    var load = {
        load: module.attr('load')
    };
    load['search'] = search;
    load['frontend'] = true;
    load['offset'] = offset;

    load = $.extend(load, module.find('.data_attributes > span').data());

    var load_module = $.trim(module.attr('load'));

    if (user_csrf_token !== null) {
        load["csrf_token"] = user_csrf_token;
    }

    grid_list_request[load_module] = $.ajax({
        type: 'POST',
        url: api_request_url,
        data: load,
        async: true,
        beforeSend: function() {
            if (grid_list_request[load_module] !== null) {
                grid_list_request[load_module].abort();
                grid_list_request[load_module] = null;
            }
        },
        success: function(data) {}
    }).done(function(data) {
        if (isJSON(data)) {
            data = $.parseJSON(data);

            if (data.subtabs != undefined) {
                $.each(data.subtabs, function(key, val) {

                    var subtab = data.subtabs[key];
                    var attributes = '';

                    if (subtab.attributes !== undefined) {
                        $.each(subtab.attributes, function(attrkey, attrval) {
                            attributes = attributes+attrkey+'="'+subtab.attributes[attrkey]+'" ';
                        });
                    }

                    subtabs = subtabs + '<li class="'+subtab.class+'" '+attributes+'> <span>';

                    subtabs = subtabs + '<img src="'+subtab.image+'" onerror="on_error_img(this)"/>';
                    subtabs = subtabs + '</span> </li>';
                });

                module.find('.subtabs > ul').html(subtabs);
                module.find('.subtabs').show();
            }

            if (append === undefined) {
                module.find('.results > div > ul').addClass(load.load);
                module.find('.results > div > ul').html('');
            }

            if (data.offset !== undefined) {
                module.attr('offset', data.offset);
            }

            if (data.content !== undefined) {
                $.each(data.content, function(key, val) {

                    var item = data.content[key];
                    var attributes = '';

                    if (item.attributes !== undefined) {
                        $.each(item.attributes, function(attrkey, attrval) {
                            attributes = attributes+attrkey+'="'+item.attributes[attrkey]+'" ';
                        });
                    }

                    var list = '<li class="'+item.class+'" '+attributes+'> <span>';

                    list = list + '<img src="'+item.image+'" onerror="on_error_img(this)"/>';
                    list = list + '</span> </li>';

                    module.find('.results > div > ul').append(list);
                });
            }

        } else {
            console.log('ERROR : ' + data);
        }
        module.find('.results').removeClass('loading');
    }).fail(function(qXHR, textStatus, errorThrown) {
        if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
            module.find('.results > div > ul').html('');
        }
        module.find('.results').removeClass('loading');
    });
}

function on_error_img(image, mode = 1) {

    if (mode === 2) {
        image.parentElement.parentElement.classList.add('error');
    } else {
        image.parentElement.parentElement.style.display = "none";
        image.parentElement.parentElement.remove();
    }
}