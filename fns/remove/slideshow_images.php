<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$slideshow_images = array();

if (role(['permissions' => ['super_privileges' => 'slideshows']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    if (isset($data['slideshow_image'])) {
        if (!is_array($data['slideshow_image'])) {
            $slideshow_images[] = $data["slideshow_image"];
        } else {
            $slideshow_images = $data["slideshow_image"];
        }
    }

    if (!empty($slideshow_images)) {

        foreach ($slideshow_images as $slideshow_image) {

            if (isset($data['slideshow'])) {
                $data['slideshow'] = sanitize_filename($data['slideshow']);
            }

            if (isset($data['slideshow']) && !empty($data['slideshow'])) {
                $slideshow_image = 'assets/files/slideshows/'.$data['slideshow'].'/'.sanitize_filename($slideshow_image);
                files('delete', ['delete' => $slideshow_image, 'real_path' => true]);
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = 'slideshow_images';
    }
}

?>