<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$sticker_packs = array();

if (role(['permissions' => ['stickers' => 'delete']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    if (isset($data['sticker_pack'])) {
        if (!is_array($data['sticker_pack'])) {
            $sticker_packs[] = $data["sticker_pack"];
        } else {
            $sticker_packs = $data["sticker_pack"];
        }
    }

    if (!empty($sticker_packs)) {

        if (isset($data['sticker'])) {

            $sticker_pack = $sticker_packs[0];
            $sticker_pack = sanitize_filename($sticker_pack);
            $stickers = array();

            if (!is_array($data['sticker'])) {
                $stickers[] = $data["sticker"];
            } else {
                $stickers = $data["sticker"];
            }

            foreach ($stickers as $sticker) {

                $sticker = sanitize_filename($sticker);

                if (!empty($sticker_pack) && !empty($sticker)) {
                    $sticker = 'assets/files/stickers/'.$sticker_pack.'/'.$sticker;
                    files('delete', ['delete' => $sticker, 'real_path' => true]);
                }
            }

        } else {
            foreach ($sticker_packs as $sticker_pack) {
                $sticker_pack = 'assets/files/stickers/'.sanitize_filename($sticker_pack);
                files('delete', ['delete' => $sticker_pack, 'real_path' => true]);
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['sticker_packs', 'sticker_pack'];
    }
}
?>