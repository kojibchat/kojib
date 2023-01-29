<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'profanity_filter']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $blacklist = $whitelist = '';

    $status = ["enable", "disable", "strict_mode"];

    if (!isset($data['status']) || empty($data['status'])) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    } else if (!in_array($data['status'], $status)) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    }


    if ($noerror) {

        if (isset($data['blacklist']) && !empty($data['blacklist'])) {

            $blacklist = "<?php \n";
            $blacklist .= 'array_push($badwords,';

            $words = preg_split("/\r\n|\n|\r/", $data['blacklist']);
            $words = array_unique($words);
            $total_words = count($words);
            $word_index = 1;

            foreach ($words as $word) {
                $word = strip_tags($word);
                if (!empty(trim($word))) {
                    $blacklist .= "\n".'"'.addslashes($word).'"';
                    if ($total_words !== $word_index) {
                        $blacklist .= ',';
                    }
                }
                $word_index = $word_index+1;
            }

            $blacklist .= "\n);";
        }

        $build = fopen("fns/filters/blacklist.php", "w");
        fwrite($build, $blacklist);
        fclose($build);




        if (isset($data['whitelist']) && !empty($data['whitelist'])) {

            $whitelist = "<?php \n";
            $whitelist .= '$whitelist = [';

            $words = preg_split("/\r\n|\n|\r/", $data['whitelist']);
            $words = array_unique($words);
            $total_words = count($words);
            $word_index = 1;

            foreach ($words as $word) {
                $word = strip_tags($word);
                if (!empty(trim($word))) {
                    $whitelist .= '"'.addslashes($word).'"';
                    if ($total_words !== $word_index) {
                        $whitelist .= ',';
                    }
                }
                $word_index = $word_index+1;
            }

            $whitelist .= "];";
        }

        $build = fopen("fns/filters/whitelist.php", "w");
        fwrite($build, $whitelist);
        fclose($build);

        if ($data['status'] !== Registry::load('settings')->profanity_filter) {
            DB::connect()->update("settings", ["value" => $data['status'], "updated_on" => Registry::load('current_user')->time_stamp], ["setting" => 'profanity_filter']);
            cache(['rebuild' => 'settings']);
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}
?>