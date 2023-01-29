var push_service_provider = $.trim($('.web_push_service_variables > .provider').text());

if (push_service_provider === 'webpushr') {

    var public_key = $.trim($('.web_push_service_variables > .public_key').text());

    (function(w, d, s, id) {
        if (typeof(w.webpushr) !== 'undefined') return;
        w.webpushr = w.webpushr || function() {
            (w.webpushr.q = w.webpushr.q || []).push(arguments)
        };
        var js, fjs = d.getElementsByTagName(s)[0];
        js = d.createElement(s);
        js.id = id;
        js.async = 1;
        js.src = "https://cdn.webpushr.com/app.min.js";
        fjs.parentNode.appendChild(js);
    }(window, document, 'script', 'webpushr-jssdk'));
    webpushr('setup', {
        'key': public_key,
        'integration': 'popup'
    });

    function _webpushrScriptReady() {
        webpushr('fetch_id', function (subscriber_id) {
            if (window.localStorage.getItem('webpushr_sid') != subscriber_id) {
                window.localStorage.setItem('webpushr_sid', subscriber_id);
                setTokenSentToServer(false);
                add_push_subscriber(subscriber_id, 'webpushr');
            }
        });
    }


} else if (push_service_provider === 'onesignal') {

    window.addEventListener('load', function() {
        var OneSignal = window.OneSignal || [];

        var oneSignal_options = {};

        var appId = $.trim($('.web_push_service_variables > .appId').text());
        var safari_web_id = $.trim($('.web_push_service_variables > .safari_web_id').text());
        var prompt_meessage = $.trim($('.web_push_service_variables > .prompt_message').text());
        var prompt_accept_button = $.trim($('.web_push_service_variables > .prompt_accept_button').text());
        var prompt_cancel_button = $.trim($('.web_push_service_variables > .prompt_cancel_button').text());
        var navigation_scope = $.trim($('.web_push_service_variables > .navigation_scope').text());
        var service_worker_path = 'assets/service_worker/';

        if (navigation_scope !== '/') {
            navigation_scope = navigation_scope+'/';
            service_worker_path = navigation_scope.replace(/^\/|\/$/g, '');
            service_worker_path = service_worker_path+'/assets/service_worker/';
        }

        var oneSignal_options = {
            appId: appId,
            safari_web_id: safari_web_id,
            allowLocalhostAsSecureOrigin: true,
            promptOptions: {
                slidedown: {
                    prompts: [{
                        type: "push",
                        autoPrompt: true,
                        text: {
                            actionMessage: prompt_meessage,
                            acceptButton: prompt_accept_button,
                            cancelButton: prompt_cancel_button
                        },
                        delay: {
                            pageViews: 1,
                            timeDelay: 20
                        }
                    }]
                }
            }
        };
        window._oneSignalInitOptions = oneSignal_options;
        window.OneSignal.push(function() {
            OneSignal.SERVICE_WORKER_UPDATER_PATH = service_worker_path+"OneSignalSDKUpdaterWorker.js";
            OneSignal.SERVICE_WORKER_PATH = service_worker_path+"OneSignalSDKWorker.js";
            OneSignal.SERVICE_WORKER_PARAM = {
                scope: navigation_scope+'assets/service_worker/'
            };
            OneSignal.init(window._oneSignalInitOptions);
            OneSignal.showSlidedownPrompt()
            OneSignal.on('subscriptionChange', function(isSubscribed) {
                if (isSubscribed) {
                    OneSignal.getUserId(function(userId) {
                        setTokenSentToServer(false);
                        add_push_subscriber(userId, 'onesignal');
                    });

                }
            });
        });
    });
}

function isTokenSentToServer() {
    return window.localStorage.getItem('devicetoken_sentToServer') === '1';
}

function setTokenSentToServer(sent) {
    window.localStorage.setItem('devicetoken_sentToServer', sent ? '1': '0');
}


function add_push_subscriber(device_token, service_provider) {
    if (!isTokenSentToServer()) {
        if (device_token !== undefined) {
            var data = {
                add: 'push_subscriber',
                service_provider: service_provider
            };
            data['device_token'] = device_token;

            if (user_csrf_token !== null) {
                data["csrf_token"] = user_csrf_token;
            }

            $.ajax({
                type: 'POST',
                url: api_request_url,
                data: data,
                async: true,
                success: function(data) {}
            }).done(function(data) {
                setTokenSentToServer(true);
                console.log('Push Sevice : Token Updated');
            });
        }
    }
}


function send_device_player_id(device_token) {
    if (device_token !== undefined) {
        var data = {
            add: 'push_subscriber'
        };
        data['device_token'] = device_token;
        
        if (user_csrf_token !== null) {
            data["csrf_token"] = user_csrf_token;
        }

        $.ajax({
            type: 'POST',
            url: api_request_url,
            data: data,
            async: true,
            success: function(data) {}
        }).done(function(data) {
            console.log('Push Sevice : Added Device Player ID');
        });
    }
}