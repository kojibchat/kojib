<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';
$noerror = true;
$disabled = 0;
$user_id = Registry::load('current_user')->id;
$all_files = array();
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$result['highlight'] = [];

if ($force_request || role(['permissions' => ['storage' => 'upload_files']])) {
    $audio_file_formats = ['audio/wav', 'audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/ogg', 'audio/x-wav'];
    $image_file_formats = ['image/jpeg', 'image/png', 'image/x-png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/webp'];
    $video_file_formats = ['video/mp4', 'video/mpeg', 'video/ogg', 'video/webm'];
    $doc_file_formats = ['text/comma-separated-values', 'application/msword', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'application/pdf', 'application/mspowerpoint', 'application/vnd.ms-powerpoint', 'application/mspowerpoint',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/mspowerpoint', 'application/mspowerpoint', 'text/rtf', 'text/plain', 'application/msexcel', 'application/vnd.ms-excel',
        'application/msexcel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.spreadsheet-template',
        'application/vnd.oasis.opendocument.presentation-template'
    ];

    if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {
        $video_file_formats = ['video/3gpp', 'video/mp4', 'video/mpeg', 'video/ogg', 'video/quicktime', 'video/webm', 'video/x-m4v',
            'video/ms-asf', 'video/x-ms-wmv', 'video/x-msvideo'];
    }


    if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {
        include('fns/FFMpeg/load.php');
    }

    $max_file_upload_size = role(['find' => 'max_file_upload_size']);
    $maximum_storage_space = role(['find' => 'maximum_storage_space']);

    if (empty($max_file_upload_size)) {
        $max_file_upload_size = 0;
    }

    if (empty($maximum_storage_space)) {
        $maximum_storage_space = 0;
    }
    if (!$force_request) {
        if (isset($_FILES['file_attachments']['name']) && !empty($_FILES['file_attachments']['name'])) {
            $totalFileSize = array_sum($_FILES['file_attachments']['size']);
            $totalFileSize = number_format($totalFileSize / 1048576, 2);
            if ($totalFileSize > $max_file_upload_size) {
                $noerror = false;
                $result = array();
                $result['success'] = false;
                $result['error_message'] = Registry::load('strings')->exceeded_max_file_upload_size;
                $result['error_key'] = 'exceeded_max_file_upload_size';
            } else {
                $location = 'assets/files/storage/'.$user_id.'/files/';

                $storage_space = files('getsize', ['getsize_of' => $location, 'real_path' => true, 'original_value' => true]);
                $storage_space = number_format($storage_space / 1048576, 2);
                $storage_space = $storage_space+$totalFileSize;

                if ($storage_space > $maximum_storage_space) {
                    $noerror = false;
                    $result = array();
                    $result['success'] = false;
                    $result['error_message'] = Registry::load('strings')->storage_limit_exceeded;
                    $result['error_key'] = 'storage_limit_exceeded';
                }
            }
        }
    }


    if ($noerror) {
        if (isset($data['user_id'])) {
            if ($force_request || role(['permissions' => ['storage' => 'super_privileges']])) {
                $data['user_id'] = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data['user_id'])) {
                    $user_id = $data['user_id'];
                }
            }
        }

        $location = 'assets/files/storage/'.$user_id.'/files/';
        $thumbnails_folder = 'assets/files/storage/'.$user_id.'/thumbnails/';

        if (!file_exists($location)) {
            mkdir($location, 0755, true);
        }

        if (!file_exists($thumbnails_folder)) {
            mkdir($thumbnails_folder, 0755, true);
        }

        if (isset($_FILES['file_attachments']['name']) && !empty($_FILES['file_attachments']['name'])) {
            $upload_info = [
                'upload' => 'file_attachments',
                'folder' => $location,
                'prepend_random_string' => true,
                'real_path' => true,
                'sanitize_filename' => true,
                'multi_upload' => true,
                'max_files' => 15
            ];

            if (!$force_request && !role(['permissions' => ['allowed_file_formats' => 'all_file_formats']])) {
                $upload_info['only_allow'] = array();

                if (role(['permissions' => ['allowed_file_formats' => 'image_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $image_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'video_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $video_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'audio_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $audio_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'documents']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $doc_file_formats);
                }

                if (empty($upload_info['only_allow'])) {
                    $upload_info['only_allow'] = ['disallow/disallow'];
                }
            }

            $files = files('upload', $upload_info);

            if ($files['result']) {
                if (isset($files['files'])) {
                    foreach ($files['files'] as $index => $file) {
                        $file_type = $file['file_type'];
                        $attachment_type = 'other_files';

                        $file_name = basename($file['file']);
                        $file_name = explode(Registry::load('config')->file_seperator, $file_name, 2);

                        if (isset($file_name[1])) {
                            $file_name = $file_name[1];
                        } else {
                            $file_name = $file_name[0];
                        }

                        if (isset(Registry::load('settings')->image_moderation) && Registry::load('settings')->image_moderation === 'enable') {
                            if (in_array($file_type, $image_file_formats)) {
                                if (!empty(Registry::load('settings')->sightengine_api_user) && !empty(Registry::load('settings')->sightengine_api_secret)) {

                                    $image_location = $file['file'];
                                    $skip_image = false;
                                    $maxmimum_score = 70;
                                    $image_moderation_params = array(
                                        'media' => new CurlFile($image_location),
                                        'models' => 'nudity,wad,offensive,gore',
                                        'api_user' => Registry::load('settings')->sightengine_api_user,
                                        'api_secret' => Registry::load('settings')->sightengine_api_secret
                                    );

                                    $sightengine_request = curl_init('https://api.sightengine.com/1.0/check.json');
                                    curl_setopt($sightengine_request, CURLOPT_POST, true);
                                    curl_setopt($sightengine_request, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($sightengine_request, CURLOPT_POSTFIELDS, $image_moderation_params);
                                    $sightengine_response = curl_exec($sightengine_request);
                                    curl_close($sightengine_request);

                                    $sightengine_response = json_decode($sightengine_response, true);

                                    if (!empty($sightengine_response) && $sightengine_response['status'] === 'success') {

                                        if (isset(Registry::load('settings')->image_removal_criteria->partial_nudity)) {

                                            $maxmimum_score = Registry::load('settings')->minimum_score_required_partial_nudity;
                                            if (empty($maxmimum_score) || (int)$maxmimum_score > 100) {
                                                $maxmimum_score = 70;
                                            } else {
                                                $maxmimum_score = 100 - (int)$maxmimum_score;
                                            }

                                            if (isset($sightengine_response['nudity']['partial'])) {
                                                $content_score = $sightengine_response['nudity']['partial'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'partial_nudity';
                                                    $skip_image = true;
                                                }
                                            }
                                        }

                                        if (isset(Registry::load('settings')->image_removal_criteria->explicit_nudity)) {

                                            $maxmimum_score = Registry::load('settings')->minimum_score_required_explicit_nudity;
                                            if (empty($maxmimum_score) || (int)$maxmimum_score > 100) {
                                                $maxmimum_score = 70;
                                            } else {
                                                $maxmimum_score = 100 - (int)$maxmimum_score;
                                            }

                                            if (isset($sightengine_response['nudity']['raw'])) {
                                                $content_score = $sightengine_response['nudity']['raw'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'explicit_nudity';
                                                    $skip_image = true;
                                                }
                                            }
                                        }

                                        if (isset(Registry::load('settings')->image_removal_criteria->offensive_signs_gestures)) {

                                            $maxmimum_score = Registry::load('settings')->minimum_score_required_offensive;
                                            if (empty($maxmimum_score) || (int)$maxmimum_score > 100) {
                                                $maxmimum_score = 70;
                                            } else {
                                                $maxmimum_score = 100 - (int)$maxmimum_score;
                                            }

                                            if (isset($sightengine_response['offensive']['prob'])) {
                                                $content_score = $sightengine_response['offensive']['prob'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'offensive';
                                                    $skip_image = true;
                                                }
                                            }
                                        }

                                        if (isset(Registry::load('settings')->image_removal_criteria->graphic_violence_gore)) {

                                            $maxmimum_score = Registry::load('settings')->minimum_score_required_graphic_violence_gore;
                                            if (empty($maxmimum_score) || (int)$maxmimum_score > 100) {
                                                $maxmimum_score = 70;
                                            } else {
                                                $maxmimum_score = 100 - (int)$maxmimum_score;
                                            }

                                            if (isset($sightengine_response['gore']['prob'])) {
                                                $content_score = $sightengine_response['gore']['prob'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'gore';
                                                    $skip_image = true;
                                                }
                                            }
                                        }

                                        if (isset(Registry::load('settings')->image_removal_criteria->wad_content)) {

                                            $maxmimum_score = Registry::load('settings')->minimum_score_required_wad_content;
                                            if (empty($maxmimum_score) || (int)$maxmimum_score > 100) {
                                                $maxmimum_score = 70;
                                            } else {
                                                $maxmimum_score = 100 - (int)$maxmimum_score;
                                            }

                                            if (isset($sightengine_response['weapon'])) {
                                                $content_score = $sightengine_response['weapon'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'weapon';
                                                    $skip_image = true;
                                                }
                                            }

                                            if (isset($sightengine_response['alcohol'])) {
                                                $content_score = $sightengine_response['alcohol'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'alcohol';
                                                    $skip_image = true;
                                                }
                                            }

                                            if (isset($sightengine_response['drugs'])) {
                                                $content_score = $sightengine_response['drugs'];
                                                $content_score = (float)$content_score*100;

                                                if ((int)$content_score > (int)$maxmimum_score) {
                                                    $reason_for_removal = 'drugs';
                                                    $skip_image = true;
                                                }
                                            }
                                        }

                                        if ($skip_image) {
                                            unlink($image_location);
                                            continue;
                                        }

                                    }
                                }
                            }
                        }


                        if (in_array($file_type, $image_file_formats)) {
                            $attachment_type = 'image_files';
                            $resize = [
                                'resize' => $file['file'],
                                'width' => 250,
                                'crop' => true,
                                'real_path' => true,
                                'saveas' => 'assets/files/storage/'.$user_id.'/thumbnails/'.basename($file['file'])
                            ];

                            if (files('resize_img', $resize)) {
                                $all_files[$attachment_type][$index]['thumbnail'] = $resize['saveas'];

                                if (isset(Registry::load('settings')->compress_image_files) && Registry::load('settings')->compress_image_files === 'enable') {

                                    $image_type = $file_type;
                                    if ($image_type === 'image/jpeg' || $image_type === 'image/png' || $image_type === 'image/bmp' || $image_type === 'image/x-ms-bmp') {

                                        $webp_file_name = pathinfo($file['file'], PATHINFO_FILENAME);
                                        $webp_file_name = 'assets/files/storage/'.$user_id.'/files/'.$webp_file_name.'.webp';

                                        $create_webp = [
                                            'file' => $file['file'],
                                            'real_path' => true,
                                            'saveas' => $webp_file_name
                                        ];


                                        if (files('create_webp', $create_webp)) {

                                            if (file_exists($file['file'])) {
                                                unlink($file['file']);
                                            }

                                            $file['file'] = $webp_file_name;

                                            $thumb_webp_file_name = pathinfo($resize['saveas'], PATHINFO_FILENAME);
                                            $thumb_webp_file_copy = 'assets/files/storage/'.$user_id.'/thumbnails/'.$thumb_webp_file_name.'_copy.webp';
                                            $thumb_webp_file_name = 'assets/files/storage/'.$user_id.'/thumbnails/'.$thumb_webp_file_name.'.webp';

                                            $create_webp = [
                                                'file' => $resize['saveas'],
                                                'real_path' => true,
                                                'saveas' => $thumb_webp_file_name
                                            ];
                                            if (files('create_webp', $create_webp)) {

                                                if (file_exists($resize['saveas'])) {
                                                    unlink($resize['saveas']);
                                                }

                                                $all_files[$attachment_type][$index]['thumbnail'] = $thumb_webp_file_name;
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif (in_array($file_type, $audio_file_formats)) {
                            $attachment_type = 'audio_files';

                            if (isset(Registry::load('settings')->compress_audio_files) && Registry::load('settings')->compress_audio_files === 'enable') {
                                if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {
                                    $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'_compressed.mp3';

                                    $compress_audio = $ffmpeg->open($file['file']);

                                    $mp3Format = new FFMpeg\Format\Audio\Mp3();

                                    $compress_audio
                                    ->save($mp3Format, $save_in);

                                    if (file_exists($save_in)) {
                                        unlink($file['file']);
                                        rename($save_in, $file['file']);
                                    }
                                }
                            }

                        } elseif (in_array($file_type, $video_file_formats)) {
                            $attachment_type = 'video_files';

                            $all_files[$attachment_type][$index]['thumbnail'] = 'assets/files/default/video_thumb.jpg';

                            if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {

                                $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'.mp4';
                                $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file['file'], PATHINFO_FILENAME).'.jpg';
                                $video = $ffmpeg->open($file['file']);
                                $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(3))->save($thumbnail);

                                if ($file_type !== 'video/mp4') {
                                    $video->save(new FFMpeg\Format\Video\X264(), $save_in);
                                }

                                $thumbnail_resize = [
                                    'resize' => $thumbnail,
                                    'width' => 250,
                                    'crop' => true,
                                    'real_path' => true
                                ];

                                files('resize_img', $thumbnail_resize);

                                if ($file_type !== 'video/mp4') {
                                    unlink($file['file']);
                                }
                                $file_type = 'video/mp4';
                                $file['file'] = $save_in;
                                $file_name = pathinfo($file_name, PATHINFO_FILENAME).'.mp4';
                                $all_files[$attachment_type][$index]['thumbnail'] = $thumbnail;



                                if (isset(Registry::load('settings')->compress_video_files) && Registry::load('settings')->compress_video_files === 'enable') {

                                    $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'_compressed.mp4';

                                    $compress_video = $ffmpeg->open($file['file']);

                                    $bit_rate = $compress_video->getFormat()->get('bit_rate');
                                    $format = new FFMpeg\Format\Video\X264('copy', 'libx264');
                                    $format->setKiloBitrate(intval($bit_rate / 1024 * 0.8));
                                    $format->setAudioKiloBitrate(128);

                                    $compress_video
                                    ->save($format, $save_in);

                                    if (file_exists($save_in)) {
                                        unlink($file['file']);
                                        rename($save_in, $file['file']);
                                    }
                                }

                            }
                        }


                        $all_files[$attachment_type][$index]['name'] = $file_name;

                        if (strlen($file_name) > 15) {
                            $all_files[$attachment_type][$index]['trimmed_name'] = trim(mb_substr($file_name, 0, 8)).'...'.mb_substr($file_name, -8);
                        } else {
                            $all_files[$attachment_type][$index]['trimmed_name'] = $file_name;
                        }

                        $all_files[$attachment_type][$index]['file'] = $file['file'];
                        $all_files[$attachment_type][$index]['file_type'] = $file_type;
                        $all_files[$attachment_type][$index]['file_size'] = files('getsize', ['getsize_of' => $file['file'], 'real_path' => true]);
                    }
                }
            }

            if (!empty($data['user_id'])) {
                $current_time_stamp = Registry::load('current_user')->time_stamp;
                DB::connect()->update('site_users', ['updated_on' => $current_time_stamp], ['user_id' => $data['user_id']]);
            }

            $result = array();
            $result['success'] = true;

            if (isset($data['frontend'])) {
                $result['todo'] = 'reload';
                $result['reload'] = 'site_user_files';
            } else {
                $result['files'] = $all_files;
            }
        }
    }
}