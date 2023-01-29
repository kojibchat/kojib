<?php

if (role(['permissions' => ['stickers' => 'edit']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $result = array();
    $noerror = true;
    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['name']) || empty($data['name']) || !isset($data['sticker_pack']) || empty($data['sticker_pack'])) {
        $result['error_variables'][] = ['name'];
        $noerror = false;
    } else {
        $data['name'] = sanitize_filename($data['name']);
        $data['sticker_pack'] = sanitize_filename($data['sticker_pack']);
        if (file_exists('assets/files/stickers/'.$data['name']) && $data['name'] !== $data['sticker_pack']) {
            $result['error_variables'][] = ['name'];
            $result['error_message'] = Registry::load('strings')->already_exists;
            $result['error_key'] = 'already_exists';
            $noerror = false;
        }
    }

    if ($noerror) {
        $oldlocation = 'assets/files/stickers/'.$data['sticker_pack'];
        $newlocation = 'assets/files/stickers/'.$data['name'];

        if ($oldlocation !== $newlocation) {
            rename($oldlocation, $newlocation);
        }

        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            if (isImage($_FILES['image']['tmp_name'])) {

                $extension = pathinfo($_FILES['image']['name'])['extension'];
                $filename = 'sticker_pack_icon.png';

                if (files('upload', ['upload' => 'image', 'folder' => $newlocation, 'saveas' => $filename, 'overwrite' => true, 'real_path' => true])['result']) {
                    files('resize_img', ['resize' => $newlocation.'/'.$filename, 'width' => 150, 'crop' => true, 'real_path' => true]);
                }
            }
        }

        if (isset($_FILES['stickers']['name']) && !empty($_FILES['stickers']['name'])) {
            if (is_array($_FILES['stickers']['name'])) {
                $filename = 'sticker.png';
            } else {
                $filename = 'sticker-'.random_string(['length' => 6]).'.png';
            }

            $upload_info = [
                'upload' => 'stickers',
                'folder' => $newlocation,
                'saveas' => $filename,
                'real_path' => true,
                'multi_upload' => true,
                'only_allow' => ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp']
            ];

            $stickers = files('upload', $upload_info);

            if ($stickers['result']) {
                if (isset($stickers['files'])) {
                    foreach ($stickers['files'] as $index => $file) {

                        $resize = [
                            'resize' => $file['file'],
                            'width' => 210,
                            'crop' => true,
                            'real_path' => true
                        ];

                        files('resize_img', $resize);
                    }
                }
            }

        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['sticker_packs', 'sticker_pack'];

    }
}
?>