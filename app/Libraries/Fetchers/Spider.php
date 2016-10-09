<?php

namespace App\Libraries\Fetchers;

use PHPHtmlParser\Dom;
use \Curl\Curl;

class Spider
{
    protected function curl() {
        $curl = new Curl;
        $curl->setCookieFile(tmpfile());
        return $curl;
    }


    public function get ($url, $args=[], $opts=[])
    {
        $curl = new Curl;
        $html = $curl->get($url, $args);

        foreach ($opts as $k => $v) {
            $curl->setopt($$k, $v);
        }

        $curl->close();
        return $html;
    }


    public function post ($url, $args=[], $cb = null)
    {
        $curl = new Curl;

        if ($cb) {
            $cb($curl);
        }

        $html = $curl->post($url, $args);

        $curl->close();
        return $html;
    }


    public function loadUrl($url)
    {
        $dom = new Dom;
        $dom->loadFromUrl($url);
        return $dom;
    }


    public function loadStr($str)
    {
        $dom = new Dom;
        $dom->load($str);
        return $dom;
    }


    public function clean ($txt, array $trims=[])
    {
        $trims = array_merge(['&nbsp;'], $trims);
        $txt = str_replace($trims, '', $txt);

        return html_entity_decode(trim($txt));
    }


    public function getViewState ($html)
    {
        $dom = (substr($html, 0, 4) === 'http') ?
             $this->loadUrl($html) : $this->loadStr($html);
        $field1 = $dom->find('input[name=__VIEWSTATE]', 0);
        $field2 = $dom->find('input[name=__EVENTVALIDATION]', 0);
 
        if (count($field1->getAttribute('value')) AND count($field2->getAttribute('value')))
        {
            return [
                '__VIEWSTATE' => $field1->getAttribute('value'),
                '__EVENTVALIDATION' => $field2->getAttribute('value'),
            ];
        }
        else
        {
            return [];
        }
    }
}
