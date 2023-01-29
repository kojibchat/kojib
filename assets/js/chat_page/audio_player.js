var load_audio_player_request = null;
var audio_player = document.getElementById("audio_player");
var audio_player_play_btn = $('.audio_player_controls > div > .controls > .play_btn > i');
var audio_player_enabled = false;
var mini_audio_player_enabled = false;
var current_audio_subtitle_element = '.audio_player_box > .header > .info > .currently_playing > .subtitle > span';

var mini_audio_player_play_btn = $('.main .aside > .mini_audio_player > div > .right > .controls > span.play_audio > i');

if ($('.audio_player_box > .header').length > 0) {
    audio_player_enabled = true;
    current_audio_subtitle_element += ',.main .aside > .mini_audio_player > div > .center > .description > span';
}

if ($('.main .aside > .mini_audio_player').length > 0) {
    mini_audio_player_enabled = true;
}


$('.main').on('click', '.load_audio_player', function(e) {
    open_module('.audio_player_box', '.main .aside', true);
    open_column('first');

    if (mini_audio_player_enabled) {
        $('.main .aside > .mini_audio_player').addClass('d-none');
    }

    if (!$('.audio_player_box').hasClass('opened') || $(this).attr('audio_content_id') !== undefined || $(this).attr('refresh') !== undefined) {

        var audio_content_id = null;

        if ($(this).attr('audio_content_id') !== undefined) {
            audio_content_id = $(this).attr('audio_content_id');
        }

        load_audio_player(audio_content_id);
    }

    if (!$('.audio_player_box').hasClass('opened')) {

        $('.audio_player_box').addClass('opened');

        $('.audio_player_box > .header > .info > .currently_playing > .audio_player_controls > audio').hide();

        $(current_audio_subtitle_element).marquee({
            duration: 12000,
            gap: 50,
            delayBeforeStart: 500,
            pauseOnHover: true,
            direction: 'left',
            startVisible: true,
            duplicated: true
        });
    }
});

$('.main').on('click', '.mini_audio_player > div > .right > .controls > span', function(e) {
    if (!$(".audio_player_box > .header").hasClass('d-none')) {

        $total_tracks = $('.audio_player_box > .playlist > div > ul > li').length;

        if ($(this).hasClass('play_audio')) {
            $('.audio_player_box .currently_playing > .audio_player_controls > div > .controls > .play_btn').trigger('click');
        } else if ($total_tracks == 0) {

            var audio_content_id = null;

            if ($(this).parent().attr('audio_content_id') !== undefined) {
                audio_content_id = $(this).parent().attr('audio_content_id');
            }

            load_audio_player(audio_content_id, true);

        } else if ($(this).hasClass('prev_track')) {
            $('.audio_player_box .currently_playing > .audio_player_controls > div > .controls > .previous_audio').trigger('click');
        } else if ($(this).hasClass('next_track')) {
            $('.audio_player_box .currently_playing > .audio_player_controls > div > .controls > .next_audio').trigger('click');
        }
    } else {

        var audio_content_id = null;

        if ($(this).parent().attr('audio_content_id') !== undefined) {
            audio_content_id = $(this).parent().attr('audio_content_id');
        }

        load_audio_player(audio_content_id, true);
    }
});

$('.main').on('input', '.audio_player_range_control', function(e) {
    $(this).css('background', 'linear-gradient(to right, var(--audio-player-tertiary-bg-color) 0%, var(--audio-player-tertiary-bg-color) '+this.value +'%, var(--audio-player-quinary-bg-color) ' + this.value + '%, var(--audio-player-quinary-bg-color) 100%)');

});


$('.main').on('input', '.audio_player_volume_control', function(e) {
    var volume = $(this).val();
    WebStorage('set', 'audio_player_volume', volume);
    audio_player.volume = volume/100;
});

$('.main').on('click', '.audio_player_box > .header > .audio_player_controls > div > .volume', function(e) {
    if ($(this).find('.control').is(":visible")) {
        $(this).find('.control').hide();
    } else {
        $(this).find('.control').show();
    }
});

$(document).click(function (e) {
    if ($(e.target).parents(".audio_player_controls").length === 0) {
        $('.audio_player_box > .header > .audio_player_controls > div > .volume > .control').hide();
    }
    if ($(".audio_player_box > .header .currently_playing").hasClass('autoplay') && $(".audio_player_box > .header .currently_playing").hasClass('window_loaded')) {

        $(".audio_player_box > .header .currently_playing").removeClass('autoplay');
        $(".audio_player_box > .header .currently_playing").removeClass('window_loaded');

        if (!$(".audio_player_box > .header").hasClass('d-none')) {
            setTimeout(function() {
                audio_player.play();
            }, 500);
        } else {

            var audio_content_id = null;

            if ($(this).attr('audio_content_id') !== undefined) {
                audio_content_id = $(this).attr('audio_content_id');
            }

            load_audio_player(audio_content_id, true);
        }
    }
});

$(window).on("load", function() {

    if (mini_audio_player_enabled) {
        if ($('.main .aside > .audio_player_box').hasClass('hidden')) {
            $('.main .aside > .mini_audio_player').removeClass('d-none');
        }
    }

    $(".audio_player_box > .header .currently_playing").addClass('window_loaded');

    if (WebStorage('get', 'audio_player_volume') !== null) {
        var audio_volume = WebStorage('get', 'audio_player_volume');
        $('.audio_player_volume_control').val(audio_volume).trigger("input");
    } else {
        $('.audio_player_volume_control').val(100).trigger("input");
    }
});


$('.main').on('click', '.load_audio', function(e) {
    var parent = $(this).parent();

    $('.audio_player_box > .playlist > div > ul > li').removeClass('current_playing');
    $(this).parent().parent().addClass('current_playing');

    var audio_tag = $('.audio_player_box > .header > .info > .currently_playing > .audio_player_controls > audio');

    createCookie('audio_current_playing_id', $(this).attr('audio_content_id'), 30);

    if ($(this).attr('audio_file_name') !== undefined) {
        createCookie('audio_current_playing_file_name', $(this).attr('audio_file_name'), 30);
    } else {
        createCookie('audio_current_playing_file_name', '', 30);
    }
    $('.audio_player_box > .header').removeClass('d-none');
    $('.audio_player_box > .header > .info > .currently_playing > .title').text(parent.parent().find('.info > .title').text());
    $('.audio_player_box > .header > .info > .currently_playing > .subtitle > span').marquee('destroy').text(parent.find('.audio_description').text());
    $('.audio_player_box > .header > .info > .image > span > img').attr('src', parent.parent().find('.image > img').attr('src'));

    if (mini_audio_player_enabled) {
        if ($('.main .aside > .audio_player_box').hasClass('hidden')) {
            $('.main .aside > .mini_audio_player').removeClass('d-none');
        }

        $('.main .aside > .mini_audio_player > div > .center > .title').text(parent.parent().find('.info > .title').text());
        $('.main .aside > .mini_audio_player > div > .center > .description > span').marquee('destroy').text(parent.find('.audio_description').text());
        $('.main .aside > .mini_audio_player > div > .left > .image > img').attr('src', parent.parent().find('.image > img').attr('src'));
    }


    $(current_audio_subtitle_element).marquee({
        duration: 12000,
        gap: 50,
        delayBeforeStart: 500,
        pauseOnHover: true,
        direction: 'left',
        startVisible: true,
        duplicated: true
    });

    audio_tag.find('source').attr('src', $(this).attr('audio_url'));

    $('.audio_player_controls > div > .controls > .play_btn').attr('audio_type', $(this).attr('audio_type'));

    if ($(this).attr('mime_type') !== undefined) {
        audio_tag.find('source').attr('type', $(this).attr('mime_type'));
    } else {
        audio_tag.find('source').removeAttr('type');
    }

    $(".audio_player_controls > div > .seek_bar > div > .current_timestamp > span").text('00:00');
    $(".audio_player_controls > div > .seek_bar > div > .duration > span").text('00:00');
    $(".audio_player_seekbar").val(0).trigger("input");

    audio_player.pause();
    audio_player.load();
    audio_player.play();

});



$(".audio_player_seekbar").on("mouseup", function () {
    audio_player.play();
});

$(".audio_player_seekbar").on("mousedown", function () {
    audio_player.pause();
});

window.onload = function() {
    $(".audio_player_seekbar").bind('touchstart', function() {
        if (is_touch_device()) {
            audio_player.pause();
        }
    }).bind('touchend', function(event) {
        if (is_touch_device()) {

            var offset = $(this).offset();
            var left = (event.changedTouches[0].pageX - offset.left);
            var totalWidth = $(".audio_player_seekbar").width();
            var percentage = (left / totalWidth);

            if (isFinite(audio_player.duration)) {
                var audioTime = audio_player.duration * percentage;
                audio_player.currentTime = audioTime;
            }

            audio_player.play();
        }
    });
}

$(".audio_player_seekbar").on("click", function(e) {
    var offset = $(this).offset();
    var left = (e.pageX - offset.left);
    var totalWidth = $(".audio_player_seekbar").width();
    var percentage = (left / totalWidth);
    var audioTime = audio_player.duration * percentage;
    audio_player.currentTime = audioTime;
});


if (audio_player_enabled) {
    audio_player.ontimeupdate = function() {
        var audio_type = $('.audio_player_controls > div > .controls > .play_btn').attr('audio_type');
        if (audio_type !== 'radio_station') {

            if (isFinite(audio_player.currentTime)) {
                $(".audio_player_controls > div > .seek_bar > div > .current_timestamp > span").text(timestamp_convertor(audio_player.currentTime));
            }

            if (isFinite(audio_player.duration)) {
                $(".audio_player_controls > div > .seek_bar > div > .duration > span").text(timestamp_convertor(audio_player.duration));
            }

            var percentage = (audio_player.currentTime / audio_player.duration) * 100;

            if (isFinite(percentage)) {
                $(".audio_player_seekbar").val(percentage).trigger("input");
            }
        }
    };

    audio_player.addEventListener('play', function() {

        if (audio_message_preview !== undefined && audio_message_preview !== null) {
            audio_message_preview.pause();
        }

        if (video_preview !== undefined && video_preview !== null) {
            video_preview.pause();
        }

        audio_player_play_btn.removeClass('bi-play-fill');
        audio_player_play_btn.addClass('bi-pause-fill');

        if (mini_audio_player_enabled) {
            mini_audio_player_play_btn.removeClass('bi-play-fill');
            mini_audio_player_play_btn.addClass('bi-pause-fill');
        }

        if ($('.audio_player_box .play_audio').attr('audio_type') !== 'radio_station') {
            $('.audio_player_seekbar').removeAttr('disabled');
            $('.audio_player_box > .header > .audio_player_controls > div > .seek_bar').removeClass('disabled');
        } else {
            $('.audio_player_seekbar').attr('disabled', 'disabled');
            $('.audio_player_box > .header > .audio_player_controls > div > .seek_bar').addClass('disabled');
        }
    });

    audio_player.addEventListener('pause', function() {
        audio_player_play_btn.removeClass('bi-pause-fill');
        audio_player_play_btn.addClass('bi-play-fill');

        if (mini_audio_player_enabled) {
            mini_audio_player_play_btn.removeClass('bi-pause-fill');
            mini_audio_player_play_btn.addClass('bi-play-fill');
        }

    });

    audio_player.addEventListener('ended', function() {
        $(".audio_player_seekbar").val(0).trigger("input");

        audio_player_play_btn.removeClass('bi-pause-fill');
        audio_player_play_btn.addClass('bi-play-fill');

        if (mini_audio_player_enabled) {
            mini_audio_player_play_btn.removeClass('bi-pause-fill');
            mini_audio_player_play_btn.addClass('bi-play-fill');
        }

        if ($('.audio_player_box > .playlist > div > ul > li.current_playing').is(':last-child')) {
            $('.audio_player_box > .playlist > div > ul > li').first().find('.load_audio').trigger('click');
        } else {
            $('.audio_player_box > .playlist > div > ul > li.current_playing').next('li').find('.load_audio').trigger('click');
        }
    });

}


$('.main').on('click', '.audio_player_box .previous_audio', function(e) {

    var current_playing = $('.audio_player_box > .playlist > div > ul > li.current_playing');

    if (current_playing.length === 0 || current_playing.is(':first-child')) {
        $('.audio_player_box > .playlist > div > ul > li.playable').last().find('.load_audio').trigger('click');
    } else {
        $('.audio_player_box > .playlist > div > ul > li.current_playing').prevAll('li.playable').eq(0).find('.load_audio').trigger('click');
    }
});

$('.main').on('click', '.audio_player_box .next_audio', function(e) {
    var current_playing = $('.audio_player_box > .playlist > div > ul > li.current_playing');

    if (current_playing.length === 0 || current_playing.is(':last-child')) {
        $('.audio_player_box > .playlist > div > ul > li.playable').first().find('.load_audio').trigger('click');
    } else {
        $('.audio_player_box > .playlist > div > ul > li.current_playing').nextAll('li.playable').eq(0).find('.load_audio').trigger('click');
    }

});


$('.main').on('click', '.audio_player_box .play_audio', function(e) {

    if (audio_player.paused && audio_player.currentTime >= 0 && !audio_player.started) {

        if ($(this).attr('audio_type') !== undefined && $(this).attr('audio_type') === 'radio_station') {
            audio_player.pause();
            audio_player.load();
        }

        audio_player.play();

    } else {
        audio_player.pause();
    }

});


function load_audio_player(audio_content_id, autoplay) {

    var search = null;
    var list = '';

    if (audio_content_id === undefined) {
        audio_content_id = null;
    }

    if (autoplay === undefined) {
        autoplay = false;
    }

    var loader_content = '';
    for (let i = 0; i < 16; i++) {
        loader_content = loader_content+'<li>';
        loader_content = loader_content+'<span class="audio_player_loading">';
        loader_content = loader_content+'<span class="image"></span>';
        loader_content = loader_content+'<span class="content">';
        loader_content = loader_content+'<span class="title"></span>';
        loader_content = loader_content+'<span class="description"></span>';
        loader_content = loader_content+'</span>';
        loader_content = loader_content+'</span>';
        loader_content = loader_content+'</li>';
    }

    $('.audio_player_box > .playlist > div > ul').html(loader_content);
    $('.audio_player_box > .zero_results').addClass('d-none');
    $('.audio_player_box > .playlist').removeClass('d-none');
    $(".audio_player_box > .playlist").scrollTop(0);

    var data = {
        load: "audio_player",
    };

    if (audio_content_id !== null) {
        data['audio_content_id'] = audio_content_id;
    }
    
    if (user_csrf_token !== null) {
        data["csrf_token"] = user_csrf_token;
    }

    load_audio_player_request = $.ajax({
        type: 'POST',
        url: api_request_url,
        data: data,
        async: true,
        beforeSend: function() {
            if (load_audio_player_request !== null) {
                load_audio_player_request.abort();
                load_audio_player_request = null;
            }
        },
        success: function(data) {}
    }).done(function(data) {
        if (isJSON(data)) {
            data = $.parseJSON(data);

            if (data.loaded !== undefined) {

                var button_attributes = '';

                if (data.loaded.button_attributes !== undefined) {
                    $.each(data.loaded.button_attributes, function(attrkey, attrval) {
                        button_attributes = button_attributes+attrkey+'="'+data.loaded.button_attributes[attrkey]+'" ';
                    });
                }

                var button = '<span '+button_attributes+'>'+data.loaded.button.text+'</span>';

                $('.audio_player_box > .heading > div > .title').replace_text(data.loaded.title);
                $('.audio_player_box > .heading > div > .button').html(button);
            }

            if (data.content !== undefined) {

                $('.audio_player_box > .playlist > div > ul').html('');

                $.each(data.content, function(key, val) {

                    var item = data.content[key];
                    var option_attributes = '';

                    if (item.option_attributes !== undefined) {
                        $.each(item.option_attributes, function(attrkey, attrval) {
                            option_attributes = option_attributes+attrkey+'="'+item.option_attributes[attrkey]+'" ';
                        });
                    }

                    var list = '<li class="'+item.class+'">';
                    list = list + '<div class="image">';
                    list = list + '<img src="'+item.image+'">';
                    list = list + '</div>';

                    list = list + '<div class="info">';
                    list = list + '<span class="title">'+item.title+'</span>';
                    list = list + '<span class="subtitle">'+item.subtitle+'</span>';
                    list = list + '</div>';

                    list = list + '<div class="options">';
                    list = list + '<span class="option '+item.option.class+'" '+option_attributes+'>'+item.option.text+'</span>';

                    if (item.description !== undefined) {
                        list = list + '<span class="audio_description d-none">'+item.description+'</span>';
                    }

                    list = list + '</div>';

                    list = list + '</li>';

                    $('.audio_player_box > .playlist > div > ul').append(list);
                });
                if (autoplay) {
                    $(".audio_player_box > .playlist > div > ul > li.playable:first-child > .options > .load_audio").addClass('clicked').trigger('click');
                }
            } else {
                $('.audio_player_box > .playlist').addClass('d-none');
                $('.audio_player_box > .zero_results').removeClass('d-none');
            }

        } else {
            console.log('ERROR : ' + data);
        }

    }).fail(function(qXHR, textStatus, errorThrown) {
        if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
            $('.audio_player_box > .playlist > div > ul').html('');
            console.log('ERROR : ' + data);
        }
    });
}