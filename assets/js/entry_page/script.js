var baseurl = site_url = $.trim($('base').attr('href'));
var api_request_url = site_url+'api_request/';
var slideshow_interval = null;
var open_custom_page_request = null;
var user_csrf_token = null;

function isJSON (data) {
    var IS_JSON = true;
    try
    {
        var json = $.parseJSON(data);
    }
    catch(err) {
        IS_JSON = false;
    }
    return IS_JSON;
}

$(document).ready(function() {
    $('body').on('contextmenu', 'img', function(e) {
        return false;
    });
});

$.fn.replace_text = function(text) {
    text = decode_specialchars(text);
    this.text(text);
    return this;
}

function decode_specialchars(text) {
    return text.replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'");
}


$("body").on('click', '.switch_form', function() {
    switch_form($(this).attr('form'));
});

function switch_form(form) {
    var form_element = '.'+form+'_form';

    $('.entry_box .tabs').addClass('d-none');
    $(".entry_box .form_container > .message").removeClass('error success warning').hide();
    $('.entry_box .tabs > ul > li').removeClass('selected');
    $('.form_element').addClass('d-none');

    $(form_element).removeClass('d-none');

    $('.entry_box .tabs > ul > li[form="'+form+'"]').addClass('selected');

    if (form !== 'forgot_password') {
        $('.entry_box .tabs').removeClass('d-none');
    }
}



$(".entry_box").on('click', function(e) {
    if (!$(e.target).hasClass('dropdown_button') && $(e.target).parents('.dropdown_button').length == 0) {
        $(".dropdown_list").addClass('d-none');
    }
});


$("body").on('mouseenter', '.dropdown_button', function(e) {
    if ($(window).width() > 767.98) {
        $(this).find(".dropdown_list").removeClass('d-none');
    }
});

$("body").on('click', '.dropdown_button', function(e) {
    if ($(window).width() < 767.98) {
        $(this).find(".dropdown_list").removeClass('d-none');
    }
});

$("body").on('mouseleave', '.dropdown_button', function(e) {
    if ($(window).width() > 767.98) {
        $(".dropdown_list").addClass('d-none');
    }
});

$(window).on('load', function() {
    $('.preloader').hide();

    if ($('.background > .slideshow').length > 0) {
        slideshow();
    }

    if ($('.cookie_consent_modal').length > 0) {
        var cookie_consent_modal = new bootstrap.Modal($(".cookie_consent_modal"));
        cookie_consent_modal.show();
    }
});

$("body").on('click', '.cookie_consent_modal .modal-footer > button', function(e) {
    if ($(this).attr('accept') !== undefined && $(this).attr('accept') === 'true') {
        createCookie('cookie_constent', 'accepted', 30);
    } else {
        window.location.href = "about:blank";
    }
});



function slideshow() {
    var active_slide = $('.background > .slideshow > ul > li.active').not(".last-active");

    if (active_slide.length === 0) {
        $('.background > .slideshow > ul > li.active').removeClass('last-active active');
        active_slide = $('.background > .slideshow > ul > li:last-child');
        active_slide.addClass('active');
    }

    var next_slide = active_slide.next();

    if (next_slide.length === 0) {
        next_slide = $('.background > .slideshow > ul > li:first-child');
    }

    active_slide.addClass('last-active');
    active_slide.removeClass('active last-active');

    next_slide.css({
        opacity: 0.0
    })
    .addClass('active')
    .animate({
        opacity: 1.0
    }, 1000, function() {});

    if (slideshow_interval != null) {
        clearTimeout(slideshow_interval);
    }
    slideshow_interval = setTimeout("slideshow()", 6000);
}

$('body').on('click', '.open_link', function(e) {
    var link = $(this).attr("link");

    if ($(this).attr('open_in_popup') !== undefined && $(this).attr('open_in_popup') === 'true') {

        var width = 650;
        var height = 450;

        if ($(this).attr('window_width') !== undefined) {
            width = $(this).attr('window_width');
        }

        if ($(this).attr('window_height') !== undefined) {
            width = $(this).attr('window_height');
        }

        var popupWindow = window.open(link, 'popupWindow', 'width='+width+',height='+height+',scrollbars=yes');

        window.closePopupWindow = function () {
            popupWindow.close();
        }

    } else if ($(this).attr('open_in_new_tab') !== undefined && $(this).attr('open_in_new_tab') === 'true') {
        window.open(link, '_blank');
    } else {
        window.location = link;
    }
    return false;
});

$(document).ready(function() {
    var first_load = $.trim($('.first_load').text());
    var alert_message = $.trim($('.alert_message').text());
    if (first_load !== '') {
        $('.entry_box .tabs > ul > li[form="'+first_load+'"]').trigger('click');
    }
    if (alert_message !== '') {
        $(".entry_box .form_container > .message > .text").replace_text(alert_message);
        $(".entry_box .form_container > .message").addClass($('.alert_message').attr('type')).fadeIn();
    }
});


$('body').on('keypress', '.entry_box .form_container > form', function(e) {
    if (!$(e.target).is("textarea")) {
        if (e.which == 13) {
            e.preventDefault();
            $('.entry_box .submit_form > span:visible').eq(0).trigger('click');
        }
    }
});

$('body').on('click', '.entry_box > div > .top > .logo > a', function(e) {
    e.preventDefault();
    location.reload(true);
});

$('body').on('click', '.entry_box .submit_form > span', function(e) {
    e.preventDefault();
    var submit_button = $(this);

    if (!submit_button.hasClass('processing')) {

        submit_button.addClass('processing');
        $(".entry_box .form_container > .message").removeClass('error success warning').hide();
        $(".entry_box .form_container > .message > .text").text('');
        $('.entry_box .form_container > form > .field > .error').removeClass('error');

        var form = '#'+submit_button.attr('form');
        var data = new FormData($(form)[0]);

        $.ajax({
            url: api_request_url,
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            async: true,
            data: data,
            type: 'post',
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                if (data === null) {
                    location.reload(true);
                } else {
                    if (data.reset_form !== undefined) {
                        $(form).trigger("reset");
                    }

                    if (data.alert !== undefined) {
                        alert(decode_specialchars(data.alert));
                    }

                    if (data.success) {

                        if (data.todo == 'redirect') {
                            window.location.href = data.redirect;
                        } else if (data.todo == 'register_login_session') {
                            window.location.href = data.redirect;
                        } else if (data.todo == 'refresh' || data.todo == 'reload') {
                            location.reload(true);
                        } else if (data.todo == 'consolelog' && data.log !== undefined) {
                            console.log(data.log);
                        } else if (data.todo == 'alert' && data.message !== undefined) {
                            $(".entry_box .form_container > .message > .text").replace_text(data.message);
                            if (data.type !== undefined) {
                                $(".entry_box .form_container > .message").addClass(data.type).fadeIn();
                            }
                        }
                    } else {

                        if (data.error_message !== undefined) {
                            $(".entry_box .form_container > .message > .text").replace_text(data.error_message);

                            if (data.error_message_append_html !== undefined) {
                                $(".entry_box .form_container > .message > .text").append(data.error_message_append_html);
                            }

                            if (data.error_type !== undefined) {
                                $(".entry_box .form_container > .message").addClass(data.error_type).fadeIn();
                            } else {
                                $(".entry_box .form_container > .message").addClass('error').fadeIn();
                            }
                        }

                        if (data.error_variables !== undefined) {

                            $.each(data.error_variables, function(index, field_name) {
                                $('.entry_box .form_container > '+form+' > .field [name="'+field_name+'"]').addClass('error');
                            });

                        }

                        if (data.error_message !== undefined) {
                            $(".entry_box > div").animate({
                                scrollTop: "0px"
                            });
                        }
                    }
                }
            } else {
                console.log('ERROR : ' + data);
            }

            submit_button.removeClass('processing');

        }) .fail(function(qXHR, textStatus, errorThrown) {

            submit_button.removeClass('processing');

        });
    }
});



function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    } else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}



$('body').on('click', '.entry_box .load_page', function(e) {
    e.preventDefault();
    var submit_button = $(this);
    var custom_page_modal = new bootstrap.Modal($(".custom_page_modal"));

    if (!submit_button.hasClass('processing')) {

        submit_button.addClass('processing');
        custom_page_modal.hide();

        var data = {
            load: 'custom_page_content',
            page_id: $(this).attr('page_id'),
        };

        open_custom_page_request = $.ajax({
            url: api_request_url,
            async: true,
            data: data,
            type: 'POST',
            beforeSend: function() {
                if (open_custom_page_request != null) {
                    open_custom_page_request.abort();
                    open_custom_page_request = null;
                }
            },
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);

                if (data.title != undefined) {
                    $('.custom_page_modal .page_title').replace_text(data.title);
                }

                if (data.page_content != undefined) {
                    $('.custom_page_modal .page_content').html(data.page_content);
                }
                custom_page_modal.show();
            } else {
                console.log('ERROR : ' + data);
            }

            submit_button.removeClass('processing');

        }) .fail(function(qXHR, textStatus, errorThrown) {

            submit_button.removeClass('processing');

        });
    }
});