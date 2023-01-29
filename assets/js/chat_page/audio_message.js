var isEdge = navigator.userAgent.indexOf('Edge') !== -1 && (!!navigator.msSaveOrOpenBlob || !!navigator.msSaveBlob);
var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
var isStereoAudioRecorder = false;
var EnableStereoAudioRecorder = true;

var recorder;
var microphone;

function captureMicrophone(callback) {

    if (microphone) {
        callback(microphone);
        return;
    }

    if (typeof navigator.mediaDevices === 'undefined' || !navigator.mediaDevices.getUserMedia) {
        alert('This browser does not supports WebRTC getUserMedia API.');

        if (!!navigator.getUserMedia) {
            alert('This browser seems supporting deprecated getUserMedia API.');
        }
    }

    navigator.mediaDevices.getUserMedia({
        audio: isEdge ? true: {
            echoCancellation: false
        }
    }).then(function(mic) {
        callback(mic);
    }).catch(function(err) {
        alert('Unable to capture your microphone.');
    });
}

function calculateTimeDuration(secs) {
    var hr = Math.floor(secs / 3600);
    var min = Math.floor((secs - (hr * 3600)) / 60);
    var sec = Math.floor(secs - (hr * 3600) - (min * 60));

    if (min < 10) {
        min = "0" + min;
    }

    if (sec < 10) {
        sec = "0" + sec;
    }

    if (hr <= 0) {
        return min + ':' + sec;
    }

    return hr + ':' + min + ':' + sec;
}

function stopRecordingCallback() {

    $(".recording_timestamp").text('00:00');
    $('.record_audio_message').removeClass('send_audio_message');
    $('.main .chatbox > .footer > .editor .message_editor .note-editor .note-toolbar').removeClass('recording_audiomsg');
    $(".recording_timestamp,.cancel_recording").addClass('d-none');
    if (microphone) {
        microphone.stop();
        microphone = null;
    }
}

function sendRecordingCallback() {

    $(".recording_timestamp").text('00:00');
    $('.record_audio_message').removeClass('send_audio_message');
    $('.main .chatbox > .footer > .editor .message_editor .note-editor .note-toolbar').removeClass('recording_audiomsg');
    $(".recording_timestamp,.cancel_recording").addClass('d-none');
    if (microphone) {
        microphone.stop();
        microphone = null;
    }


    var blob = recorder.getBlob();
    var fileName = getFileName('webm');
    var audio_message = new File([blob], fileName, {
        type: 'audio/webm'
    });


    var content = {
        'audio_message': audio_message,
        'blob': blob,
    };

    send_message(content);
}

function getFileName(fileExtension) {
    var d = new Date();
    var year = d.getUTCFullYear();
    var month = d.getUTCMonth();
    var date = d.getUTCDate();
    return 'Audio_Message-' + year + month + date + '-' + getRandomString() + '.' + fileExtension;
}

function getRandomString() {
    if (window.crypto && window.crypto.getRandomValues && navigator.userAgent.indexOf('Safari') === -1) {
        var a = window.crypto.getRandomValues(new Uint32Array(3)),
        token = '';
        for (var i = 0, l = a.length; i < l; i++) {
            token += a[i].toString(36);
        }
        return token;
    } else {
        return (Math.random() * new Date().getTime()).toString(36).replace(/\./g, '');
    }
}


$('body').on('click', ".record_audio_message", function(e) {

    var btnStartRecording = $(this);

    if (!btnStartRecording.hasClass('send_audio_message')) {

        if (!microphone) {
            captureMicrophone(function(mic) {
                microphone = mic;

                if (isSafari) {

                    alert('Please click startRecording button again. First time we tried to access your microphone. Now we will record it.');
                    return;
                }

                btnStartRecording.trigger('click');
            });
            return;
        }

        var options = {
            type: 'audio',
            mimeType: 'audio/wav',
            numberOfAudioChannels: isEdge ? 1: 2,
            checkForInactiveTracks: true,
            bufferSize: 16384
        };

        if (EnableStereoAudioRecorder) {
            if (isSafari || isEdge || isStereoAudioRecorder) {
                options.recorderType = StereoAudioRecorder;
            }
        }

        if (navigator.platform && navigator.platform.toString().toLowerCase().indexOf('win') === -1) {
            options.sampleRate = 48000;
        }

        if (EnableStereoAudioRecorder) {
            if (isSafari || isStereoAudioRecorder) {
                options.sampleRate = 44100;
                options.bufferSize = 4096;
                options.numberOfAudioChannels = 2;
            }
        }

        if (recorder) {
            recorder.destroy();
            recorder = null;
        }

        options.timeSlice = 1000;

        if (EnableStereoAudioRecorder) {
            if (isSafari || isEdge || isStereoAudioRecorder) {
                $(".recording_timestamp").replace_text(language_string('recording'));
            }
        }

        options.onTimeStamp = function(timestamp, timestamps) {
            var duration = (new Date().getTime() - timestamps[0]) / 1000;
            if (duration < 0) {
                return;
            }
            $(".recording_timestamp").text(calculateTimeDuration(duration));
        }

        recorder = RecordRTC(microphone, options);

        recorder.startRecording();

        $(".recording_timestamp,.cancel_recording").removeClass('d-none');
        $('.main .chatbox > .footer > .editor .message_editor .note-editor .note-toolbar').addClass('recording_audiomsg');

        $(this).addClass('send_audio_message');
    }
});

$('body').on('click', ".send_audio_message", function(e) {
    recorder.stopRecording(sendRecordingCallback);
});

$('body').on('click', ".cancel_recording", function(e) {
    recorder.stopRecording(stopRecordingCallback);
});