<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$site_role_ids = array();

if (role(['permissions' => ['site_roles' => 'delete']])) {
    if (isset($data['site_role_id'])) {
        if (!is_array($data['site_role_id'])) {
            $data["site_role_id"] = filter_var($data["site_role_id"], FILTER_SANITIZE_NUMBER_INT);
            $site_role_ids[] = $data["site_role_id"];
        } else {
            $site_role_ids = array_filter($data["site_role_id"], 'ctype_digit');
        }
    }


    if (!empty($site_role_ids)) {

        $columns = $where = $join = null;
        $columns = [
            'site_roles.site_role_id'
        ];

        $where["site_roles.site_role_id"] = $site_role_ids;
        $where["site_roles.site_role_attribute[!]"] = ['default_site_role', 'guest_users', 'administrators', 'unverified_users', 'banned_users'];

        $validate_site_roles = DB::connect()->select('site_roles', $columns, $where);
        $site_role_ids = array();

        foreach ($validate_site_roles as $valid_site_role) {
            $site_role_ids[] = $valid_site_role['site_role_id'];
        }

    }

    if (!empty($site_role_ids)) {

        $default_site_role_id = DB::connect()->select("site_roles", ["site_role_id"], ["site_role_attribute" => "default_site_role"]);

        if (isset($default_site_role_id[0])) {
            $default_site_role_id = $default_site_role_id[0]['site_role_id'];
        } else {
            $default_site_role_id = 1;
        }

        DB::connect()->update("site_users", ["site_role_id" => $default_site_role_id], ["site_role_id" => $site_role_ids]);

        DB::connect()->delete("site_roles", ["site_role_id" => $site_role_ids]);

        if (!DB::connect()->error) {

            foreach ($site_role_ids as $site_role_id) {
                $site_role_names[] = 'site_role_'.$site_role_id;

                foreach (glob("assets/files/site_roles/".$site_role_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
            }

            language(['delete_string' => $site_role_names]);

            cache(['rebuild' => 'site_roles']);

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
?>