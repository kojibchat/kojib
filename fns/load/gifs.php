<?php

include 'fns/curl/load.php';
use Curl\Curl;

if (Registry::load('settings')->gif_search_engine !== 'disable') {
    if (role(['permissions' => ['private_conversations' => 'attach_gifs', 'groups' => 'attach_gifs'], 'condition' => 'OR'])) {

        $limit = Registry::load('settings')->gifs_per_load;
        $data["offset"] = htmlspecialchars($data["offset"]);
        $content_filter = '';

        if (Registry::load('settings')->gif_search_engine === 'tenor') {

            $search = 'featured?';

            if (!empty(Registry::load('settings')->gif_content_filtering)) {
                $content_filter = '&contentfilter='.Registry::load('settings')->gif_content_filtering;
            }

            if (!empty($data["search"])) {
                $search = 'search?q='.urlencode(htmlspecialchars($data["search"]));
            }

            $gif_url = 'https://tenor.googleapis.com/v2/'.$search.'&limit='.$limit.'&media_filter=gif,tinygif'.$content_filter;

            if (!empty(Registry::load('settings')->gif_search_engine_api)) {
                $gif_url = $gif_url.'&key='.Registry::load('settings')->gif_search_engine_api;
            }

            if (!empty($data["offset"])) {
                $gif_url = $gif_url.'&pos='.$data["offset"];
            }
        } else if (Registry::load('settings')->gif_search_engine === 'gfycat') {

            if (Registry::load('settings')->gif_content_filtering === 'high') {
                $content_filter = '&rating=g';
            } else if (Registry::load('settings')->gif_content_filtering === 'medium') {
                $content_filter = '&rating=pg';
            } else if (Registry::load('settings')->gif_content_filtering === 'low') {
                $content_filter = '&rating=pg-13';
            }

            if (!empty($data["search"])) {
                $search = 'search?tag='.urlencode(htmlspecialchars($data["search"]));
                $gif_url = 'https://api.gfycat.com/v1/gfycats/search?search_text='.$search.'&count='.$limit.$content_filter;

                if (!empty($data["offset"])) {
                    $gif_url = $gif_url.'&cursor='.$data["offset"];
                }

            } else {
                $gif_url = 'https://api.gfycat.com/v1/gfycats/trending?count='.$limit.$content_filter;

                if (!empty($data["offset"])) {
                    $gif_url = $gif_url.'&cursor='.$data["offset"];
                }
            }

        } else if (Registry::load('settings')->gif_search_engine === 'giphy') {

            $search = 'trending?';

            if (!empty($data["search"])) {
                $search = 'search?q='.urlencode(htmlspecialchars($data["search"]));
            }

            if (Registry::load('settings')->gif_content_filtering === 'high') {
                $content_filter = '&rating=g';
            } else if (Registry::load('settings')->gif_content_filtering === 'medium') {
                $content_filter = '&rating=pg';
            } else if (Registry::load('settings')->gif_content_filtering === 'low') {
                $content_filter = '&rating=pg-13';
            }

            $gif_url = 'https://api.giphy.com/v1/gifs/'.$search.'&limit='.$limit.$content_filter;

            if (!empty(Registry::load('settings')->gif_search_engine_api)) {
                $gif_url = $gif_url.'&api_key='.Registry::load('settings')->gif_search_engine_api;
            }

            if (!empty($data["offset"])) {
                $gif_url = $gif_url.'&offset='.$data["offset"];
            }


        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_TIMEOUT, 15);
        $curl->setOpt(CURLOPT_ENCODING, '');
        $curl->get($gif_url);

        if (!$curl->error) {
            $gifs = $curl->response;

            if (isset($gifs->next)) {
                if (!empty($gifs->next)) {
                    $output['offset'] = $gifs->next;
                } else {
                    $output['offset'] = 'endofresults';
                }
            } else if (isset($gifs->cursor)) {
                if (!empty($gifs->cursor)) {
                    $output['offset'] = $gifs->cursor;
                } else {
                    $output['offset'] = 'endofresults';
                }
            } else if (isset($gifs->pagination->count)) {
                if (!empty($gifs->pagination->count)) {
                    $output['offset'] = $gifs->pagination->count+$gifs->pagination->offset;
                } else {
                    $output['offset'] = 'endofresults';
                }
            }

            if (isset($gifs->results)) {
                $gifs = $gifs->results;
            } else if (isset($gifs->gfycats)) {
                $gifs = $gifs->gfycats;
            } else if (isset($gifs->data)) {
                $gifs = $gifs->data;
            } else {
                $gifs = array();
            }

            $i = 0;

            foreach ($gifs as $gif) {
                $image_url = null;

                if (isset($gif->media_formats->tinygif->url)) {
                    $image_url = $gif->media_formats->tinygif->url;
                } else if (isset($gif->max1mbGif)) {
                    $image_url = $gif->max1mbGif;
                } else if (isset($gif->images->downsized->url)) {
                    $image_url = $gif->images->downsized->url;
                }

                if (!empty($image_url)) {
                    $output['content'][$i] = new stdClass();
                    $output['content'][$i]->image = $image_url;
                    $output['content'][$i]->attributes = ['gif_url' => $image_url];
                    $output['content'][$i]->class = "attach_gif";
                }
                $i = $i+1;
            }
        }
    }
}