<?php

require('fns/url_metadata/Embera/Autoloader.php');
use Embera\Embera;

function url_metadata($url, $private_data = array()) {

    session_write_close();
    ignore_user_abort(false);
    set_time_limit(20);

    $result = array();
    $result['success'] = false;
    $twitter = false;
    $tiktok = false;
    $advanced = true;
    $image_found = false;
    $curl_timeout = 10;
    $proxy = $proxy_auth = $proxy_type = $proxy_port = null;
    $og_url = '';

    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (filter_var($url, FILTER_VALIDATE_URL) !== FALSE) {

        $result['title'] = $result['image'] = null;
        $result['description'] = '';
        $result['host_name'] = $result['url'] = $result['mime_type'] = null;
        $result['host_name'] = parse_url($url, PHP_URL_HOST);
        $result['url'] = $url;
        $curl_url = $url;

        $embera_config = [
            'instagram_access_token' => 'yourtokenforinsta',
            'facebook_access_token' => 'fb_Access_token'
        ];

        $embera_request = new Embera($embera_config);

        $link_meta_data = $embera_request->getUrlData([$curl_url]);

        if ($embera_request->hasErrors()) {
            $result['error_message'] = $embera_request->getLastError();
        }

        if (!empty($link_meta_data)) {
            $link_meta_data = reset($link_meta_data);
            $provider_name = $link_meta_data['provider_name'];
            $provider_name = preg_replace("/[^a-zA-Z0-9_]+/", "", $provider_name);

            if (!empty($provider_name)) {
                $load_fn_file = 'fns/url_metadata/'.$provider_name.'.php';
                if (file_exists($load_fn_file)) {
                    include($load_fn_file);
                } else {
                    $result['mime_type'] = 'text/html';
                    if (isset($link_meta_data['title'])) {
                        $result['title'] = $link_meta_data['title'];
                        $result['image'] = $link_meta_data['thumbnail_url'];

                        if (isset($link_meta_data['description'])) {
                            $result['description'] = $link_meta_data['description'];
                        } else if (isset($link_meta_data['author_name'])) {
                            $result['description'] = $link_meta_data['author_name'];
                        }
                    }
                }
            }

        }

        if (empty($result['title'])) {
            $curl_request = curl_init();
            curl_setopt($curl_request, CURLOPT_HEADER, 0);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_request, CURLOPT_ENCODING, "");

            if (mb_strripos($url, 'twitter.com/') !== FALSE) {
                $twitter = true;
                $url = str_replace('mobile.twitter.com/', 'twitter.com/', $url);
                curl_setopt($curl_request, CURLOPT_USERAGENT, 'Twitterbot/1.0');
            } else if (mb_strripos($url, 'vt.tiktok.com') !== FALSE) {
                curl_setopt($curl_request, CURLOPT_USERAGENT, 'facebookexternalhit/1.1');
            } else if (mb_strripos($url, 'www.amazon') !== FALSE || mb_strripos($url, 'amzn.to') !== FALSE) {
                curl_setopt($curl_request, CURLOPT_USERAGENT, 'facebookexternalhit/1.1');
            } else if (mb_strripos($url, 'facebook.com') !== FALSE) {
                curl_setopt($curl_request, CURLOPT_USERAGENT, 'facebookexternalhit/1.1');
            } else {
                curl_setopt($curl_request, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0');
            }

            curl_setopt($curl_request, CURLOPT_URL, $curl_url);

            if (!empty($proxy)) {
                curl_setopt($curl_request, CURLOPT_PROXY, $proxy);

                if (!empty($proxy_port)) {
                    curl_setopt($curl_request, CURLOPT_PROXYPORT, $proxy_port);
                }
                if (!empty($proxy_type)) {
                    curl_setopt($curl_request, CURLOPT_PROXYTYPE, 'HTTP');
                } else {
                    curl_setopt($curl_request, CURLOPT_PROXYTYPE, $proxy_type);
                }

                if (!empty($proxy_auth)) {
                    curl_setopt($curl_request, CURLOPT_PROXYUSERPWD, $proxy_auth);
                }
            }
            curl_setopt($curl_request, CURLOPT_AUTOREFERER, true);
            curl_setopt($curl_request, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl_request, CURLOPT_TIMEOUT, $curl_timeout);

            $page_content = curl_exec($curl_request);

            if (curl_errno($curl_request)) {
                $result['error_message'] = curl_error($curl_request);
            }
            $result['mime_type'] = $content_type = curl_getinfo($curl_request, CURLINFO_CONTENT_TYPE);

            curl_close($curl_request);

            if (stripos($content_type, 'text/html') !== false && !empty($page_content)) {
                $doc = new DOMDocument();
                @$doc->loadHTML(mb_convert_encoding($page_content, 'HTML-ENTITIES', 'UTF-8'));
                $finder = new DOMXPath($doc);
                $nodes = $doc->getElementsByTagName('title');

                if (isset($nodes->item(0)->nodeValue) && !empty($nodes->item(0)->nodeValue)) {
                    $result['title'] = $nodes->item(0)->nodeValue;
                }

                if ($twitter) {
                    if (mb_strripos($url, '/status/') !== FALSE) {
                        $coverpic = $finder->query('//div[@class="AdaptiveMedia-photoContainer js-adaptive-photo "]');
                        foreach ($coverpic as $link) {
                            $imgList = $finder->query("./img", $link);
                            if ($imgList->length > 0) {
                                $result['image'] = $imgList->item(0)->getAttribute('src');
                                $image_found = true;
                            }
                        }
                    }
                    if (!$image_found) {
                        $coverpic = $finder->query('//div[@class="ProfileCanopy-headerBg"]');
                        foreach ($coverpic as $link) {
                            $imgList = $finder->query("./img", $link);
                            if ($imgList->length > 0) {
                                $result['image'] = $imgList->item(0)->getAttribute('src');
                                $image_found = true;
                            }
                        }
                    }
                }

                if (mb_strripos($result['host_name'], 'youtube.com') !== FALSE || mb_stristr($result['host_name'], 'youtu.be')) {
                    if (mb_stristr($url, 'youtu.be/')) {
                        preg_match('/(https:|http:|)(\/\/www\.|\/\/|)(.*?)\/(.{11})/i', $url, $youtube_id);
                        if (isset($youtube_id[4]) && !empty($youtube_id[4])) {
                            $result['mime_type'] = 'video/youtube';
                            $result['image'] = 'http://img.youtube.com/vi/'.$youtube_id[4].'/mqdefault.jpg';
                            $image_found = true;
                        }
                    } else {
                        @preg_match('/(https:|http:|):(\/\/www\.|\/\/|)(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $youtube_id);
                        if (isset($youtube_id[5]) && !empty($youtube_id[5])) {
                            $result['mime_type'] = 'video/youtube';
                            $result['image'] = 'http://img.youtube.com/vi/'.$youtube_id[5].'/mqdefault.jpg';
                            $image_found = true;
                        }
                    }
                } else if (mb_strripos($result['host_name'], 'vimeo.com') !== FALSE) {
                    if (preg_match("/(?:https?:\/\/)?(?:www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/", $url, $vimeo_id)) {
                        if (isset($vimeo_id[3]) && !empty($vimeo_id[3])) {
                            $result['mime_type'] = 'video/vimeo';
                        }
                    }
                } else if (mb_strripos($result['host_name'], 'dailymotion.com') !== FALSE) {
                    if (preg_match('!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!', $url, $dailymotion_id)) {
                        if (isset($dailymotion_id[6]) || isset($dailymotion_id[4]) || isset($dailymotion_id[2])) {
                            $result['mime_type'] = 'video/dailymotion';
                        }
                    }
                }

                $metas = $doc->getElementsByTagName('meta');

                for ($i = 0; $i < $metas->length; $i++) {
                    $meta = $metas->item($i);

                    if (strtolower($meta->getAttribute('name')) == 'description') {
                        $result['description'] = $meta->getAttribute('content');
                    }

                    if (strtolower($meta->getAttribute('property')) == 'og:description') {
                        $alt_description = $meta->getAttribute('content');
                    }

                    if (strtolower($meta->getAttribute('property')) == 'og:image' && !$image_found) {
                        $result['image'] = $meta->getAttribute('content');
                        $image_found = true;
                    }

                    if (strtolower($meta->getAttribute('property')) == 'twitter:image') {
                        $alt_img = $meta->getAttribute('content');
                    }

                    if (strtolower($meta->getAttribute('property')) == 'og:url') {
                        $og_url = $meta->getAttribute('content');
                    }
                }
            } else if (stripos($content_type, 'application/json') !== false) {
                if ($tiktok) {

                    $result['description'] = 'TikTok, known in China as Douyin, is a video-focused social networking service owned by Chinese company ByteDance.';
                    $result['title'] = 'TikTok - Make Your Day';
                    $result['image'] = 'https://i.pinimg.com/736x/12/2b/4d/122b4d888b450fdce6f4a97f89e3ca23.jpg';

                    $extract_json = json_decode($page_content);
                    if (isset($extract_json->title) && isset($extract_json->thumbnail_url)) {
                        $result['title'] = htmlspecialchars(mb_substr($extract_json->title, 0, 100));
                        $result['image'] = $extract_json->thumbnail_url;

                        if (isset($extract_json->author_name)) {
                            $result['description'] = $extract_json->author_name;
                        }

                        if (isset($extract_json->html)) {
                            $doc = new DOMDocument();
                            @$doc->loadHTML(mb_convert_encoding($extract_json->html, 'HTML-ENTITIES', 'UTF-8'));
                            $finder = new DOMXPath($doc);
                            $nodes = $doc->getElementsByTagName('blockquote');
                            if (!empty($nodes->item(0)->getAttribute('data-video-id'))) {
                                $result['iframe_embed'] = 'https://www.tiktok.com/embed/'.$nodes->item(0)->getAttribute('data-video-id');
                            }
                        }
                    }
                }
            }

            if ($advanced) {
                if (stripos($content_type, 'image/jpeg') !== false || stripos($content_type, 'image/png') !== false || stripos($content_type, 'image/gif') !== false) {
                    $result['title'] = parse_url($url)['host'];
                    $result['image'] = $url;
                } else if (stripos($content_type, 'video/mp4') !== false || stripos($content_type, 'video/mpeg') !== false || stripos($content_type, 'video/ogg') !== false || stripos($content_type, 'video/webm') !== false) {
                    $result['title'] = parse_url($url)['host'];
                    $result['image'] = $url;
                }
            }
        }

        if (isset($alt_description) && empty($result['description'])) {
            $result['description'] = $alt_description;
        }

        if (empty($result['description'])) {
            $result['description'] = parse_url($url)['host'];
        } else {
            $result['description'] = htmlspecialchars(mb_substr($result['description'], 0, 100));
        }

        if (!empty($result['title'])) {
            $result['title'] = htmlspecialchars(mb_substr($result['title'], 0, 100));
        }

        if (isset($alt_img) && empty($result['image'])) {
            $result['image'] = $alt_img;
        }
        if (empty($result['image'])) {
            $result['image'] = null;
        } else {
            $result['image'] = filter_var($result['image'], FILTER_SANITIZE_URL);
        }
    }

    if (!empty($result['title']) && !empty($og_url)) {
        if (mb_strripos($result['url'], 'vt.tiktok.com') !== FALSE) {

            $tiktok_video_id = $og_url;
            $regex = '/(@[a-zA-z0-9]*|.*)(\/.*\/|trending.?shareId=)([\d]*)/m';

            preg_match_all($regex, $tiktok_video_id, $matches, PREG_SET_ORDER, 0);

            if (isset($matches[0][3])) {
                $tiktok_video_id = $matches[0][3];
                $result['iframe_embed'] = 'https://www.tiktok.com/embed/'.$tiktok_video_id;
                $result['iframe_class'] = 'w-auto h-75';
            }

        }
    }

    if (!empty($result['title'])) {
        $result['success'] = true;
        unset($result['error_message']);
    }

    return $result;
}