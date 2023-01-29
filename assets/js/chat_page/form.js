var load_form_request = null;
var code_editor_form = [];

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

$('body').on('click', '.load_form', function(e) {

    if ($(this).attr('form') !== undefined) {

        if ($(window).width() < 767.98) {
            $('.main .chat_page_container').removeClass('show_navigation');
        }

        open_column('third');

        var load = {
            form: $(this).attr('form')
        };

        load = $.extend(load, $(this).data());

        if ($(this).attr('enlarge') !== undefined) {
            load_form(load, true);
        } else {
            load_form(load);
        }
    }

});

$('body').on('change', '.main .formbox .switch_form', function(e) {

    if ($(this).attr('form') !== undefined) {

        var load = {
            form: $(this).attr('form')
        };

        var field_name = $(this).find('select').attr('name');

        load[field_name] = $(this).find('select').val();

        load = $.extend(load, $(this).data());

        if ($(this).attr('enlarge') !== undefined) {
            load_form(load, true);
        } else {
            load_form(load);
        }


    }
});

$('body').on('change', '.main .formbox .field > select.preview_audio_file', function(e) {

    var audio_file_location = $(this).attr('audio_location');
    audio_file_location = audio_file_location+$(this).val();

    $('.main .formbox > .form_audio_preview > div').html('');

    $("<audio></audio>").attr({
        'src': audio_file_location,
        'type': 'audio/mpeg',
    }).appendTo('.main .formbox > .form_audio_preview > div');

    var form_audio_preview = $('.main .formbox > .form_audio_preview > div > audio')[0];
    form_audio_preview.play();
});

function load_form(load, enlarge) {
    var loader_content = '';

    if (load !== undefined) {

        $('.main .middle').removeClass('col-lg-9');
        $('.main .middle').removeClass('col-lg-6');
        $('.main .middle').removeClass('col-lg-5');

        if ($(window).width() > 767.98) {
            $('.main .info_panel').addClass('d-none');
        }

        $('.main .formbox').removeClass('col-lg-3');
        $('.main .formbox').removeClass('col-lg-4');

        if (enlarge !== undefined) {
            $('.main .middle').addClass('col-lg-5');
            $('.main .formbox').addClass('col-lg-4');
        } else {
            $('.main .middle').addClass('col-lg-6');
            $('.main .formbox').addClass('col-lg-3');
        }

        $('.main .formbox').removeClass('d-none');

        $(".main .formbox").addClass('processing');
        $(".main .formbox > .fields > div > .error").text('').hide();

        for (let i = 0; i < 10; i++) {
            loader_content += '<div class="field loading">';
            loader_content += '<label>Loading</label>';
            loader_content += '<input name="loader" type="text" autocomplete="off">';
            loader_content += '</div>';
        }


        $(".main .formbox > .fields > div > form > .formdata").html(loader_content);

        $(".main .formbox > .head > .title").replace_text(language_string('loading'));
        $(".main .formbox > .bottom > span.submit").replace_text(language_string('loading'));
        $(".main .formbox > .bottom > span.submit").show();

        if (user_csrf_token !== null) {
            load["csrf_token"] = user_csrf_token;
        }

        load_form_request = $.ajax({
            type: 'POST',
            url: api_request_url,
            data: load,
            async: true,
            beforeSend: function() {
                if (load_form_request != null) {
                    load_form_request.abort();
                    load_form_request = null;
                }
            },
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                var form = '';
                var fields = data.fields;

                if (Object.keys(data).length > 0) {
                    if (data.loaded !== undefined) {
                        $(".main .formbox > .head > .title").replace_text(data.loaded.title);

                        if (data.loaded.button !== undefined) {
                            $(".main .formbox > .bottom > span.submit").replace_text(data.loaded.button);
                        } else {
                            $(".main .formbox > .bottom > span.submit").hide();
                        }
                    }

                    $.each(fields, function(key, val) {

                        field = fields[key];

                        var attributes = attribute = selected = '';
                        var parent_attributes = 'class="'+field.class+'" ';

                        if (field.attributes !== undefined) {
                            $.each(field.attributes, function(attrkey, attrval) {
                                attributes = attributes+attrkey+'="'+attrval+'" ';

                            });
                        }

                        if (field.type !== undefined && field.type == 'color') {
                            parent_attributes = 'class="'+field.class+' input-group color_picker" ';
                            attributes = attributes+'class="form-control input-lg" ';
                        }

                        if (field.child_fields !== undefined) {
                            var child_field_index = 1;

                            attributes = attributes+'child_fields="';
                            $.each(field.child_fields, function(parent_field_name, child_fields) {

                                if (child_field_index !== 1) {
                                    attributes = attributes+',';
                                }

                                attributes = attributes+parent_field_name+'|'+child_fields;
                                child_field_index++;
                            });

                            attributes = attributes+'" ';
                        }

                        if (field.parent_attributes !== undefined) {
                            $.each(field.parent_attributes, function(attrkey, attrval) {
                                parent_attributes = parent_attributes+attrkey+'="'+attrval+'" ';
                            });
                        }

                        if (field.placeholder !== undefined) {
                            attribute = 'placeholder="'+field.placeholder+'"';
                        }

                        if (field.value !== undefined) {
                            attribute = 'value="'+field.value+'"';
                        } else if (field.accept !== undefined) {
                            attribute = 'accept="'+field.accept+'"';
                        }

                        form = form + '<div '+parent_attributes+'>';

                        if (field.title !== undefined) {
                            form = form + '<label>' + field.title;
                            if (field.required !== undefined) {
                                form = form + '<i class="required">*</i>';
                            }
                            if (field.infotip !== undefined) {
                                form = form + '<i class="form-infotip bi bi-info-circle-fill" data-bs-toggle="tooltip" title="'+field.infotip+'"></i>';
                            }
                            form = form + '</label>';
                        }

                        if (field.tag == 'input' && field.type == 'date') {
                            form = form + '<input type="date" name="'+key+'" '+attribute+' '+attributes+' autocomplete="off"/>';
                            form = form + '<span class="date_selector">';
                            form = form + '<i class="bi bi-calendar-plus"></i>';
                            form = form + '</span>';
                        } else if (field.tag == 'link') {
                            form = form + '<span class="link_field">';
                            form = form + '<a href="'+field.link+'">'+field.text+'</a>';
                            form = form + '</span>';
                        } else if (field.tag == 'input' && field.type == 'file') {
                            if (field.multi_select !== undefined) {
                                key = key+'[]';
                                attribute = attribute+' multiple';
                            }
                            form = form + '<span class="selector">';
                            form = form + '<input type="file" name="'+key+'" '+attribute+'/>';
                            form = form + '<span class="file-browse"><span>'+language_string('choose_file')+'</span><i class="bi bi-folder"></i></span>';
                            form = form + '</span>';
                        } else if (field.tag == 'checkbox') {
                            form = form + '<div class="checkbox">';

                            $.each(field.options, function(optkey, optval) {
                                var option = field.options[optkey];
                                selected = '';
                                if (field.optionkey !== undefined && field.optionkey == 'optionvalue') {
                                    optkey = optval;
                                }
                                if (field.values === undefined) {
                                    if (field.value !== undefined) {
                                        field.values = field.value;
                                    }
                                }

                                if (field.values !== undefined) {
                                    if (field.values.indexOf(optkey) >= 0) {
                                        selected = "checked";
                                    }
                                } else if (field.select_all !== undefined) {
                                    selected = "checked";
                                }

                                form = form + '<label>';

                                form = form + '<span class="checkbox">';
                                form = form + '<input type="checkbox" name="'+key+'[]" '+selected+' value="'+optkey+'"/>';
                                form = form + '<span class="checkmark"></span>';
                                form = form + '</span>';

                                form = form + '<span class="checkbox_label">'+optval+'</span>';
                                form = form + '</label>';
                            });

                            form = form + '</div>';

                        } else if (field.tag == 'select') {
                            form = form + '<select name="'+key+'" autocomplete="off" '+attributes+'>';
                            form = form + '<option value="">-------</option>';

                            $.each(field.options, function(optkey, optval) {
                                var option = field.options[optkey];
                                selected = '';
                                if (field.optionkey !== undefined && field.optionkey == 'optionvalue') {
                                    optkey = optval;
                                }

                                if (field.value !== undefined && field.value == optkey) {
                                    selected = "selected";
                                }

                                form = form + '<option '+selected+' value="'+optkey+'">'+optval+'</option>';
                            });

                            form = form + '</select>';

                        } else if (field.tag == 'image_list') {
                            form = form + '<div class="image_list" '+attributes+'><div><ul>';

                            $.each(field.options, function(optkey, optval) {
                                form = form + '<li>';
                                form = form + '<input type="radio" name="'+key+'" value="'+optkey+'"/>';
                                form = form + '<img src="'+optval+'"/>';
                                form = form + '</li>';
                            });

                            form = form + '</ul></div></div>';
                        } else if (field.closetag !== undefined && field.closetag == true || field.tag == 'textarea') {
                            if (field.placeholder == undefined) {
                                attribute = '';
                            }

                            if (field.value == undefined) {
                                field.value = '';
                            } else {
                                attribute = '';
                            }

                            form = form + '<'+field.tag+' name="'+key+'" '+attribute+' '+attributes+' autocomplete="off">'+field.value+'</'+field.tag+'>';
                        } else {

                            var color_picker = false;

                            if (field.type !== undefined && field.type == 'color') {
                                field.type = 'text';
                                color_picker = true;

                                form = form + '<div class="color_selector">';
                            }

                            form = form + '<'+field.tag+' name="'+key+'" type="'+field.type+'" '+attribute+' '+attributes+' autocomplete="off"/>';

                            if (color_picker) {
                                form = form + '<span class="input-group-append">';
                                form = form + '<span class="input-group-text colorpicker-input-addon"><i></i></span>';
                                form = form + '</span>';
                                form = form + '</div>';
                            }

                        }
                        form = form + '</div>';
                    });

                    $(".main .formbox > .fields > div > form > .formdata").html(form);
                }

                adjust_form_textarea();
                append_color_picker();

                $(".main .formbox .toggle_form_fields > select").trigger('change');
                $(".main .formbox > .fields").scrollTop(0);
                $("[data-bs-toggle=tooltip]").tooltip();
                $(".main .formbox > .fields > div > form > .formdata > .filebrowse > .selector > input").hide();
                $(".main .formbox").removeClass('processing');
            } else {
                console.log('ERROR : ' + data);
                $(".main .formbox").addClass('error');
                $(".main .formbox > .head > .title").replace_text(language_string('error'));
            }
        }).fail(function(qXHR, textStatus, errorThrown) {
            if (qXHR.statusText !== 'abort' && qXHR.statusText !== 'canceled') {
                $(".main .formbox").addClass('error');
                $(".main .formbox > .head > .title").replace_text(language_string('error'));
            }
        });
    }
}

function append_color_picker() {
    $(".main .formbox > .fields > div > form > .formdata > .color_picker").each(function() {
        $(this).colorpicker({
            autoInputFallback: false
        });
    });
}

function adjust_form_textarea() {
    $(".main .formbox > .fields > div > form > .formdata textarea").each(function() {
        if ($(this).attr("rows") == undefined) {
            $(this).css("height", "auto");
            var scrollHeight = $(this).prop('scrollHeight');
            $(this).css("height", (scrollHeight+10)+"px");
        }
        if ($(this).parent().hasClass("content_editor")) {
            var content_editor = $(this);
            $(this).summernote({
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['forecolor', 'backcolor']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']]
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video', 'table']],
                    ['view', ['fullscreen', 'codeview']],
                ],
                minHeight: 300,
                codeviewFilter: false,
                tooltip: false,
                dialogsInBody: true,
                callbacks:
                {
                    onInit: function(e) {
                        // content_editor.summernote("codeview.activate");
                        let buttons = $('.main .formbox > .fields > div > form > .formdata .note-editor button[data-toggle="dropdown"]');

                        buttons.each((key, value)=> {
                            $(value).on('click', function(e) {
                                $(this).closest('.note-btn-group').toggleClass('open');
                            })
                        })
                    },

                }
            });
        } else if ($(this).parent().hasClass("code_editor")) {
            var code_editor_id = "form_code_editor";
            var code_editor_mode = "application/x-httpd-php";

            if ($(this).attr("code_editor_id") !== undefined) {
                code_editor_id = $(this).attr("code_editor_id");
            }

            if ($(this).attr("code_editor_mode") !== undefined) {
                code_editor_mode = $(this).attr("code_editor_mode");
            }

            if ($(this).parent().hasClass("d-none")) {
                $(this).parent().removeClass("d-none");
                $(this).parent().addClass("code-editor-d-none");
            }

            code_editor_form[code_editor_id] = CodeMirror.fromTextArea(document.getElementById(code_editor_id), {
                lineNumbers: true,
                lineWrapping: true,
                theme: "dracula",
                mode: code_editor_mode,
            });

            if ($(this).parent().hasClass("code-editor-d-none")) {
                $(this).parent().addClass("d-none");
                $(this).parent().removeClass("code-editor-d-none");
            }
        }
    });
}

$(document).on('click', '.main .formbox .selectinput > input', function() {
    this.setSelectionRange(0, this.value.length);
});
$(document).on('keypress', '.main .formbox .selectinput > input', function(e) {
    e.preventDefault();
});


$('body').on('click', '.main .formbox > .bottom > .submit', function(e) {
    e.preventDefault();

    if (!$(".main .formbox").hasClass('processing')) {

        $(".main .formbox").addClass('processing');
        $('.main .formbox .selectinput > input').val('');
        $(".main .formbox > .fields > div > .error").text('').hide();
        $('.main .formbox > .fields > div > form > .formdata .error').removeClass('error');

        form_base64('encode');
        var data = new FormData($('.main .formbox > .fields > div > form.dataform')[0]);

        if ($(".main .formbox > .fields > div > form > .formdata > .filebrowse > .selector > .multifiles").length) {
            var multifiles = $(".main .formbox > .fields > div > form > .formdata > .filebrowse > .selector > .multifiles").get(0).files;
            for (var i = 0; i < multifiles.length; i++) {
                data.append("multifiles["+i+"]", multifiles[i]);
            }
        }

        if ($(".main .formbox > .fields > div > form > .formdata > .field.content_editor").length) {
            var content_editor_contents = $(".main .formbox > .fields > div > form > .formdata > .field.content_editor > textarea");
            var content_editor_name = content_editor_contents.attr('name');
            data.append(content_editor_name, $(content_editor_contents).summernote('code'));
        }

        if ($(".main .formbox > .fields > div > form > .formdata > .field.code_editor").length) {

            var code_editor_id = "form_code_editor";

            $(".main .formbox > .fields > div > form > .formdata > .field.code_editor").each(function() {
                if ($(this).find('textarea').attr("code_editor_id") !== undefined) {
                    code_editor_id = $(this).find('textarea').attr("code_editor_id");
                }
                var code_editor_contents = $(this).find('textarea');
                var code_editor_name = code_editor_contents.attr('name');
                var code_editor_contents = code_editor_form[code_editor_id].getValue();

                data.append(code_editor_name, code_editor_contents);
            });
        }

        if (user_csrf_token !== null) {
            data.append("csrf_token", user_csrf_token);
        }

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
                    window.location.href = baseurl;
                } else {
                    if (data.success) {

                        if (data.alert_message !== undefined) {
                            alert(decode_specialchars(data.alert_message));
                        }

                        if (data.todo == 'reload') {

                            if (data.reload !== undefined && $.isArray(data.reload)) {
                                if (jQuery.inArray($('.main .aside > .site_records > .current_record').attr('load'), data.reload) !== -1) {
                                    $(".main .aside > .site_records > .current_record").removeClass('loading');
                                    $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                                    $(".main .aside > .site_records > .current_record > .title").trigger('click');
                                }
                            } else if (data.reload !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === data.reload) {
                                $(".main .aside > .site_records > .current_record").removeClass('loading');
                                $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                                $(".main .aside > .site_records > .current_record > .title").trigger('click');
                            }
                            $(".main .formbox > .bottom > span.cancel").trigger('click');

                        } else if (data.todo == 'refresh') {

                            if (data.on_refresh !== undefined) {
                                data.on_refresh = JSON.stringify(data.on_refresh);
                                WebStorage('set', 'load_on_refresh', data.on_refresh);
                            }

                            location.reload(true);
                        } else if (data.todo == 'consolelog' && data.log !== undefined) {
                            console.log(data.log);
                        } else if (data.todo == 'load_conversation') {

                            var load_data = [];
                            load_data[data.identifier_type] = data.identifier;
                            load_conversation(load_data);

                            if (data.reload_aside !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === 'groups') {
                                $(".main .aside > .site_records > .current_record").removeClass('loading');
                                $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                                $(".main .aside > .site_records > .current_record > .title").trigger('click');
                            }

                        }
                    } else {

                        var error_message = language_string('error')+' : '+data.error_message;
                        $(".main .formbox > .fields > div > .error").replace_text(error_message).fadeIn();

                        if (data.error_variables !== undefined) {

                            $.each(data.error_variables, function(key, val) {
                                $('.main .formbox > .fields > div > form > .formdata [name="'+val+'"]').addClass('error');
                                $('.main .formbox > .fields > div > form > .formdata [name="'+val+'"]').parent().find('.file-browse').addClass('error');
                            });

                            if (data.error_variables[0] !== undefined && $('.main .formbox > .fields > div > form > .formdata [name="'+data.error_variables[0]+'"]').length > 0) {
                                $(".main .formbox > .fields").animate({
                                    scrollTop: $('.main .formbox > .fields > div > form > .formdata [name="'+data.error_variables[0]+'"]').position().top - 50
                                }, 500);
                            }

                        } else {
                            $(".main .formbox > .fields").scrollTop(0);
                        }
                    }
                }
            } else {
                console.log('ERROR : ' + data);
            }

            form_base64('decode');
            $(".main .formbox").removeClass('processing');

        }) .fail(function(qXHR, textStatus, errorThrown) {

            $(".main .formbox").removeClass('processing');
            form_base64('decode');
        });
    }
});

function form_base64($todo = 'encode') {
    var result;
    $('.main .formbox > .fields > div > form > .formdata > .field.base_encode').each(function() {
        if ($(this).find('input').length != 0) {
            if ($todo == 'encode') {
                result = b64EncodeUnicode($(this).find('input').val());
            } else {
                result = b64DecodeUnicode($(this).find('input').val());
            }
            $(this).find('input').val(result);
        } else if ($(this).find('textarea').length != 0) {
            if ($todo == 'encode') {
                result = b64EncodeUnicode($(this).find('textarea').val());
            } else {
                result = b64DecodeUnicode($(this).find('textarea').val());
            }
            $(this).find('textarea').val(result);
        }

    });
}

function b64EncodeUnicode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
}

function b64DecodeUnicode(str) {
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

$('body').on('click', '.main .formbox > .fields > div > form >.formdata > .filebrowse > .selector > span', function(e) {
    if ($(e.target).hasClass('bi-x-lg')) {
        $(this).parent().find('input').val('');
        $(this).find('span').replace_text(language_string('choose_file'));
        $(this).find('i').removeClass('bi-x-lg');
        $(this).find('i').addClass('bi-folder');
    } else {
        $(this).parent().find('input').trigger('click');
    }
});


$('body').on('click', '.main .formbox > .fields > div > form > .formdata > .field > .colorpickicon', function() {
    $(this).parent().find('.colorpick').focus();
});


$('body').on('change', '.main .formbox > .fields > div > form >.formdata > .filebrowse > .selector > input[type=file]', function(e) {
    var filename = (e.target.files[0].name);
    if (filename.length > 25) {
        filename = (filename).substring(0, 24)+"...";
    }
    $(this).parent().find('span > span').text(filename);
    $(this).parent().find('span > i').removeClass('bi-folder');
    $(this).parent().find('span > i').addClass('bi-x-lg');
});

$('.main .formbox > .bottom > span.cancel').on('click', function(e) {
    open_column('first', true);
    $('.main .middle').removeClass('col-lg-5');
    $('.main .middle').removeClass('col-lg-6');
    $('.main .middle').addClass('col-lg-9');

    if ($(window).width() > 767.98) {
        $('.main .info_panel').addClass('d-none');
    }

    $('.main .formbox').addClass('d-none');
});




$("body").on("change", ".main .formbox .toggle_form_fields > select", function(e) {

    if ($(this).attr("child_fields") !== undefined) {
        var show_fields = $(this).attr("child_fields").split(",");
    } else {
        var show_fields = $(this).attr("show_fields").split(",");
    }

    var value_selected = $(this).val();
    var reset_value = false;

    if ($(this).attr("reset_value") !== undefined) {
        reset_value = true;
    }

    if ($(this).attr("hide_field") !== undefined) {
        $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hide_field")).addClass('d-none');
    }

    if ($(this).attr("common_field") !== undefined) {
        if (value_selected !== '' && value_selected !== 0) {
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("common_field")).removeClass('d-none');
        }
    }

    if (show_fields !== undefined) {
        $.each(show_fields, function(index, value) {
            var search_value = value.split("|");
            if (search_value[1] !== undefined && search_value[1].length > 0 && value_selected === search_value[0]) {

                if (reset_value) {
                    $('.main .formbox > .fields > div > form > .formdata > .field.'+search_value[1]).find('select').prop('selectedIndex', 0);
                }

                $('.main .formbox > .fields > div > form > .formdata > .field.'+search_value[1]).removeClass('d-none');
            }
        });
    }
});


$("body").on("change", ".main .formbox .showfieldon > select", function(e) {

    var searcharray = $(this).attr("checkvalue").split(",");
    if (jQuery.inArray($(this).val(), searcharray) !== -1) {
        $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")).removeClass('d-none');
        $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")+' input').prop('disabled', false);
        $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")+' textarea').prop('disabled', false);
        if ($(this).attr("hideclass") !== undefined) {
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")).addClass('d-none');
            if ($(this).attr("removefield_onsubmit") !== undefined) {
                $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")+' input').prop('disabled', true);
                $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")+' textarea').prop('disabled', true);
            }
        }
    } else {
        $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")).addClass('d-none');
        if ($(this).attr("removefield_onsubmit") !== undefined) {
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")+' input').prop('disabled', true);
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("fieldclass")+' textarea').prop('disabled', true);
        }
        if ($(this).attr("hideclass") !== undefined) {
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")).removeClass('d-none');
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")+' input').prop('disabled', false);
            $('.main .formbox > .fields > div > form > .formdata > .field.'+$(this).attr("hideclass")+' textarea').prop('disabled', false);
        }
    }
});

$('body').on('click', '.main .formbox > .fields > div > form > .formdata > .field > .image_list > div > ul > li', function(e) {

    if ($(this).hasClass('selected')) {
        $('.main .formbox > .fields > div > form > .formdata > .field > .image_list > div > ul > li').removeClass('selected');
        $(this).find('input').prop("checked", false);
    } else {
        $('.main .formbox > .fields > div > form > .formdata > .field > .image_list > div > ul > li').removeClass('selected');
        $(this).find('input').prop("checked", true);
        $(this).addClass('selected');
    }

});


$(document).on('click', '.main .formbox .selectfield > input', function() {
    this.setSelectionRange(0, this.value.length);
});

$(document).on('keypress', '.main .formbox .selectfield > input', function(e) {
    e.preventDefault();
});