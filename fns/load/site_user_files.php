<?php

if (role(['permissions' => ['storage' => 'access_storage']])) {

    include 'fns/files/load.php';

    $output = array();
    $columns = [
        'site_users.display_name', 'site_users.email_address'
    ];

    $image_file_formats = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp'];
    $video_file_formats = ['video/mp4', 'video/mpeg', 'video/ogg', 'video/quicktime', 'video/webm'];
    $audio_file_formats = ['audio/ogg', 'audio/mpeg', 'audio/webm'];

    $user_id = Registry::load('current_user')->id;

    if (isset($data["user_id"])) {

        if (role(['permissions' => ['storage' => 'super_privileges']])) {

            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["user_id"])) {
                $user_id = $data["user_id"];
            }
        }
    }

    $where["site_users.user_id"] = $user_id;
    $where["LIMIT"] = 1;

    $user = DB::connect()->select('site_users', $columns, $where);

    if (isset($user[0])) {
        $user = $user[0];
        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->files;

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        if (role(['permissions' => ['storage' => 'upload_files']])) {
            if ((int)$user_id === (int)Registry::load('current_user')->id) {
                $output['todo'] = new stdClass();
                $output['todo']->class = 'upload_storage_files';
                $output['todo']->title = Registry::load('strings')->upload_files;
            }
        }

        if (role(['permissions' => ['storage' => 'delete_files']])) {
            $output['multiple_select'] = new stdClass();
            $output['multiple_select']->title = Registry::load('strings')->delete;
            $output['multiple_select']->attributes['class'] = 'ask_confirmation';
            $output['multiple_select']->attributes['data-remove'] = 'site_user_files';
            $output['multiple_select']->attributes['data-user_id'] = $user_id;
            $output['multiple_select']->attributes['multi_select'] = 'file';
            $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $output['filters'][1] = new stdClass();
        $output['filters'][1]->filter = Registry::load('strings')->all;
        $output['filters'][1]->class = 'load_aside';
        $output['filters'][1]->attributes['load'] = 'site_user_files';
        $output['filters'][1]->attributes['data-user_id'] = $user_id;

        $output['filters'][2] = new stdClass();
        $output['filters'][2]->filter = Registry::load('strings')->images;
        $output['filters'][2]->class = 'load_aside';
        $output['filters'][2]->attributes['load'] = 'site_user_files';
        $output['filters'][2]->attributes['data-user_id'] = $user_id;
        $output['filters'][2]->attributes['filter'] = 'images';

        $output['filters'][3] = new stdClass();
        $output['filters'][3]->filter = Registry::load('strings')->videos;
        $output['filters'][3]->class = 'load_aside';
        $output['filters'][3]->attributes['load'] = 'site_user_files';
        $output['filters'][3]->attributes['data-user_id'] = $user_id;
        $output['filters'][3]->attributes['filter'] = 'videos';

        $output['filters'][4] = new stdClass();
        $output['filters'][4]->filter = Registry::load('strings')->audio;
        $output['filters'][4]->class = 'load_aside';
        $output['filters'][4]->attributes['load'] = 'site_user_files';
        $output['filters'][4]->attributes['data-user_id'] = $user_id;
        $output['filters'][4]->attributes['filter'] = 'audio';

        $output['filters'][5] = new stdClass();
        $output['filters'][5]->filter = Registry::load('strings')->others;
        $output['filters'][5]->class = 'load_aside';
        $output['filters'][5]->attributes['load'] = 'site_user_files';
        $output['filters'][5]->attributes['data-user_id'] = $user_id;
        $output['filters'][5]->attributes['filter'] = 'others';

        $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

        $location = 'assets/files/storage/'.$user_id.'/files/*';

        if (!empty($data["search"])) {
            $data['search'] = rangeof_chars(stripslashes(str_replace(array('/', '*'), array('', ''), $data['search'])));
            $location = $location.$data['search'].'*';
        }

        if ($data["filter"] === 'images') {
            $extensions = rangeof_chars('jpg,png,gif,jpeg,bmp');
            $location = $location.'.{'.$extensions.'}';
        } else if ($data["filter"] === 'videos') {
            $extensions = rangeof_chars('mp4,mpeg,ogg,webm,mov');
            $location = $location.'.{'.$extensions.'}';
        } else if ($data["filter"] === 'audio') {
            $extensions = rangeof_chars('oga,mp3,wav');
            $location = $location.'.{'.$extensions.'}';
        } else if ($data["filter"] === 'others') {
            $allfiles = glob($location);
            $extensions = rangeof_chars('mp4,mpeg,ogg,webm,mov,jpg,png,gif,jpeg,bmp,oga,mp3,wav');
            $location = $location.'.{'.$extensions.'}';
            $imgvideos = glob($location, GLOB_BRACE);
            $files = array_diff($allfiles, $imgvideos);
        }

        if (empty($data["filter"])) {
            $files = glob($location);
        } else if ($data["filter"] !== 'others') {
            $files = glob($location, GLOB_BRACE);
        }

        usort($files, function($file1, $file2) {
            return filemtime($file2) <=> filemtime($file1);
        });

        $files = array_slice($files, $data["offset"], Registry::load('settings')->records_per_call);

        if ((int)$user_id !== (int)Registry::load('current_user')->id && empty($data["offset"])) {
            $folder['name'] = $user['display_name'];
            $folder['folder'] = true;
            array_unshift($files, $folder);
        }

        foreach ($files as $file) {
            $output['content'][$i] = new stdClass();
            $output['content'][$i]->class = "file square";
            $user_folder = false;

            if (!isset($file['folder'])) {

                $file_type = mime_content_type($file);
                $filextension = "assets/files/file_extensions/".pathinfo($file, PATHINFO_EXTENSION).".png";
                $filename = basename($file);
                $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/file_extensions/unknown.png";

                if (in_array($file_type, $video_file_formats)) {
                    $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file, PATHINFO_FILENAME).'.jpg';
                } else {
                    $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.basename($file);
                }

                if (file_exists($thumbnail)) {
                    $output['content'][$i]->image = Registry::load('config')->site_url.$thumbnail;
                } else if (file_exists($filextension)) {
                    $output['content'][$i]->image = Registry::load('config')->site_url.$filextension;
                }

                $output['content'][$i]->class = "file sub";
            } else {
                $user_folder = true;
                $filename = $file['name'];
                $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user_id, 'gravatar' => $user['email_address']]);
                $file = 'assets/files/storage/'.$user_id.'/files/';
            }
            $output['content'][$i]->title = explode('-gr-', $filename, 2);

            if (isset($output['content'][$i]->title[1])) {
                $output['content'][$i]->title = $output['content'][$i]->title[1];
            } else {
                $output['content'][$i]->title = $filename;
            }

            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;
            $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $file, 'real_path' => true]);
            $output['content'][$i]->identifier = $filename;

            $option_index = 1;

            if (!$user_folder) {

                if (in_array($file_type, $image_file_formats)) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->view;
                    $output['options'][$i][$option_index]->class = 'preview_image';
                    $output['options'][$i][$option_index]->attributes['load_image'] = Registry::load('config')->site_url.$file;
                    $option_index++;
                } else if (in_array($file_type, $video_file_formats)) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->play;
                    $output['options'][$i][$option_index]->class = 'preview_video';
                    $output['options'][$i][$option_index]->attributes['video_file'] = Registry::load('config')->site_url.$file;
                    $output['options'][$i][$option_index]->attributes['mime_type'] = $file_type;
                    $option_index++;
                } else if (in_array($file_type, $audio_file_formats)) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->play;
                    $output['options'][$i][$option_index]->class = 'preview_video';
                    $output['options'][$i][$option_index]->attributes['video_file'] = Registry::load('config')->site_url.$file;
                    $output['options'][$i][$option_index]->attributes['thumbnail'] = Registry::load('config')->site_url.'assets/files/audio_player/images/default.png';
                    $output['options'][$i][$option_index]->attributes['mime_type'] = $file_type;
                    $option_index++;
                }


                if (isset($data["conversation_loaded"])) {
                    $data["share_files"] = true;
                }

                if (role(['permissions' => ['private_conversations' => 'attach_from_storage', 'groups' => 'attach_from_storage'], 'condition' => 'OR'])) {
                    if (isset($data["share_files"])) {
                        $output['options'][$i][$option_index] = new stdClass();
                        $output['options'][$i][$option_index]->option = Registry::load('strings')->share;
                        $output['options'][$i][$option_index]->class = 'share_file';
                        $output['options'][$i][$option_index]->attributes['file_name'] = $filename;
                        $option_index++;
                    }
                }


                if (role(['permissions' => ['storage' => 'download_files']])) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->download;
                    $output['options'][$i][$option_index]->class = 'download_file';
                    $output['options'][$i][$option_index]->attributes['download'] = 'file';
                    $output['options'][$i][$option_index]->attributes['data-user_id'] = $user_id;
                    $output['options'][$i][$option_index]->attributes['data-file_name'] = $filename;
                    $option_index++;
                }

                if (role(['permissions' => ['storage' => 'delete_files']])) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->delete;
                    $output['options'][$i][$option_index]->class = 'ask_confirmation';
                    $output['options'][$i][$option_index]->attributes['data-remove'] = 'site_user_files';
                    $output['options'][$i][$option_index]->attributes['data-user_id'] = $user_id;
                    $output['options'][$i][$option_index]->attributes['data-file'] = $filename;
                    $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->delete_file_confirmation;
                    $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $option_index++;
                }
            } else {
                if (role(['permissions' => ['storage' => 'super_privileges']])) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->delete_all;
                    $output['options'][$i][$option_index]->class = 'ask_confirmation';
                    $output['options'][$i][$option_index]->attributes['data-remove'] = 'site_user_files';
                    $output['options'][$i][$option_index]->attributes['data-user_id'] = $user_id;
                    $output['options'][$i][$option_index]->attributes['data-delete_all'] = true;
                    $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->delete_all_files_confirmation;
                    $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $option_index++;
                }
            }
            $i++;
        }
    }
}
?>