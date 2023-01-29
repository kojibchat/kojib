var slideshow_interval = null;
var slideshow_timeout = 4000;
var baseurl = site_url = $.trim($('base').attr('href'));
var api_request_url = site_url+'api_request/';
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

function slideshow() {
    var active_slide = $('.hero_section .slideshow > ul > li.active').not(".last-active");

    if (active_slide.length === 0) {
        $('.hero_section .slideshow > ul > li.active').removeClass('last-active active');
        active_slide = $('.hero_section .slideshow > ul > li:last-child');
        active_slide.addClass('active');
    }

    var next_slide = active_slide.next();

    if (next_slide.length === 0) {
        next_slide = $('.hero_section .slideshow > ul > li:first-child');
    }

    active_slide.addClass('last-active');
    active_slide.removeClass('active last-active');

    next_slide.css({
        opacity: 0.0
    })
    .addClass('active')
    .animate({
        opacity: 1.0,
    }, 1000, function() {});

    if (slideshow_interval != null) {
        clearTimeout(slideshow_interval);
    }
    slideshow_interval = setTimeout("slideshow()", slideshow_timeout);
}


$(window).on('load', function() {
    if ($('.hero_section .slideshow').length > 0) {
        if ($('.hero_section .slideshow > ul > li').length > 1) {
            slideshow();
        } else {
            $('.hero_section .slideshow > ul > li:first-child').addClass('active');
        }
    }
});

$(document).ready(function() {
    $('body').on('contextmenu', 'img', function(e) {
        return false;
    });
});


$(".landing_page").on('click', function(e) {
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


$("body").on('click', '.frequently_asked_questions .questions > .item', function(e) {
    if (!$(this).hasClass('open')) {
        $('.frequently_asked_questions .questions > .item').removeClass('open');
        $(this).addClass('open');
    } else {
        $('.frequently_asked_questions .questions > .item').removeClass('open');
    }
});