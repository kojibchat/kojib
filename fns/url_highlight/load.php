<?php
include('fns/url_highlight/autoload.php');

use VStelmakh\UrlHighlight\Highlighter\HtmlHighlighter;
use VStelmakh\UrlHighlight\Matcher\UrlMatch;

class CustomURLHighlighter extends HtmlHighlighter
{
    protected function getText(UrlMatch $match): string
    {
        $check_url = $match->getUrl();
        if (!filter_var($check_url, FILTER_VALIDATE_EMAIL)) {
            return $match->getHost();
        } else {
            return $check_url;
        }
    }
}