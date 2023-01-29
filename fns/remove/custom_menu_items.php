<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$menu_item_ids = $string_constants = array();

if (role(['permissions' => ['custom_menu' => 'delete']])) {

    if (isset($data['menu_item_id'])) {
        if (!is_array($data['menu_item_id'])) {
            $data["menu_item_id"] = filter_var($data["menu_item_id"], FILTER_SANITIZE_NUMBER_INT);
            $menu_item_ids[] = $data["menu_item_id"];
        } else {
            $menu_item_ids = array_filter($data["menu_item_id"], 'ctype_digit');
        }
    }

    if (!empty($menu_item_ids)) {

        DB::connect()->delete("custom_menu_items", ["menu_item_id" => $menu_item_ids]);

        if (!DB::connect()->error) {

            foreach ($menu_item_ids as $menu_item_id) {
                $string_constants[] = 'custom_menu_item_'.$menu_item_id;
            }

            language(['delete_string' => $string_constants]);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'custom_menu_items';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>