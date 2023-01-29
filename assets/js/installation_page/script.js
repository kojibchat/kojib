var site_url = $.trim($('base').attr('href'));
var install_request_url = site_url+'installer/';
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

$('body').on('click', '.system_requirements > .proceed > span', function(e) {
    $(".installation_page > .wrapper > .installer_box").scrollTop(0);
    $(".system_requirements").addClass('d-none');
    $(".installation_form").addClass('animate__animated animate__fadeIn');
    $(".installation_form ").removeClass('d-none');
});

$('body').on('click', '.installation_form > form > .install > span', function(e) {
    e.preventDefault();

    if (!$(".installation_form").hasClass('processing')) {

        $(".installation_form").addClass('processing');
        $(".installation_form > form > .error_message").addClass('d-none');

        var data = new FormData($('.installation_form > form')[0]);

        $.ajax({
            url: install_request_url,
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
                    $(".installation_form > form > .error_message > div > span").text('Something Went Wrong');
                    $(".installation_form > form > .error_message").removeClass('d-none');
                    $(".installation_page > .wrapper > .installer_box").scrollTop(0);
                } else {
                    if (data.success) {

                        if (data.alert_message !== undefined) {
                            alert(data.alert_message);
                        }

                        location.reload(true);
                    } else {
                        $(".installation_form > form > .error_message > div > span").text(data.error_message);
                        $(".installation_form > form > .error_message").removeClass('d-none');
                        $(".installation_page > .wrapper > .installer_box").scrollTop(0);
                    }
                }
            } else {
                $(".installation_form > form > .error_message > div > span").text('Something Went Wrong');
                $(".installation_form > form > .error_message").removeClass('d-none');
                $(".installation_page > .wrapper > .installer_box").scrollTop(0);
            }


            $(".installation_form").removeClass('processing');

        }) .fail(function(jqXHR, textStatus, errorThrown) {
            var error_message = '';

            if (jqXHR.status === 0) {
                error_message = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                error_message = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                error_message = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                error_message = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                error_message = 'Time out error.';
            } else if (exception === 'abort') {
                error_message = 'Ajax request aborted.';
            } else {
                error_message = 'Uncaught Error.\n' + jqXHR.responseText;
            }

            $(".installation_form > form > .error_message > div > span").text(error_message);
            $(".installation_form > form > .error_message").removeClass('d-none');
            $(".installation_page > .wrapper > .installer_box").scrollTop(0);

            $(".installation_form").removeClass('processing');
        });
    }
});