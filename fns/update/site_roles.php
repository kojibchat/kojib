<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->invalid_value;
$result['error_key'] = 'invalid_value';

if (role(['permissions' => ['site_roles' => 'edit']])) {

    if (isset($data['name']) && !empty($data['name'])) {
        if (isset($data['site_role_id'])) {
            $role_id = filter_var($data["site_role_id"], FILTER_SANITIZE_NUMBER_INT);
            $role_string = 'site_role_'.$role_id;

            $language_id = Registry::load('current_user')->language;

            if (isset($data["language_id"])) {
                $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

                if (!empty($data["language_id"])) {
                    $language_id = $data["language_id"];
                }
            }

            if (!empty($data['site_role_id'])) {

                $disabled = 0;
                $remove = ['name', 'process', 'edit', 'site_role_id'];
                $attribute = 'custom_site_role';
                $allowed_attributes = ['default_site_role', 'guest_users', 'administrators', 'unverified_users', 'banned_users'];
                $permissions = sanitize_array($data);
                $permissions = array_diff_key($permissions, array_flip($remove));
                $permissions = json_encode($permissions);


                if (isset($data['disabled']) && $data['disabled'] === 'yes') {
                    $disabled = 1;
                }

                if (isset($data['attribute']) && in_array($data['attribute'], $allowed_attributes)) {
                    $attribute = $data['attribute'];
                    DB::connect()->update("site_roles", ["site_role_attribute" => "custom_site_role"], ["site_role_attribute" => $attribute]);
                }

                DB::connect()->update(
                    "site_roles",
                    ["permissions" => $permissions, "site_role_attribute" => $attribute, "disabled" => $disabled, "updated_on" => Registry::load('current_user')->time_stamp],
                    ["site_role_id" => $role_id]
                );
                if (!DB::connect()->error) {
                    language(['edit_string' => $role_string, 'value' => $data['name'], 'language_id' => $language_id]);

                    cache(['rebuild' => 'site_roles']);

                    if (isset($_FILES['badge']['name']) && !empty($_FILES['badge']['name'])) {
                        if (isImage($_FILES['badge']['tmp_name'])) {
                            foreach (glob("assets/files/site_roles/".$role_id.Registry::load('config')->file_seperator."*.*") as $oldbadge) {
                                unlink($oldbadge);
                            }

                            $extension = pathinfo($_FILES['badge']['name'])['extension'];
                            $filename = $role_id.Registry::load('config')->file_seperator.random_string(['length' => 6]).'.'.$extension;

                            if (files('upload', ['upload' => 'badge', 'folder' => 'site_roles', 'saveas' => $filename])['result']) {
                                files('resize_img', ['resize' => 'site_roles/'.$filename, 'width' => 150, 'height' => 150, 'crop' => true]);
                            }
                        }
                    }

                    $result = array();
                    $result['success'] = true;
                    $result['todo'] = 'reload';
                    $result['reload'] = 'site_roles';
                } else {
                    $result['error_message'] = Registry::load('strings')->went_wrong;
                    $result['error_key'] = 'something_went_wrong';
                }
            }
        }
    } else {
        $result['error_variables'] = ['name'];
    }
}