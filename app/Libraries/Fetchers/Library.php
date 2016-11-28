<?php

namespace App\Libraries\Fetchers;
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream;
use Prewk\XmlStringStreamer\Parser;
use Overtrue\Pinyin\Pinyin;
use \Curl\Curl;

class Library extends Spider
{
    public function hotBooks()
    {
        // load page
        $dom = $this->loadUrl('http://202.114.9.3/pages/BorrowHot.aspx?Language=1&page=');
        // get items
        $list = $dom->find('ul.booklist li');

        $items = [];

        // resolve items
        foreach ($list as $li) {
            if ( ! $li) continue;
            try {
                $href = $li->find('.boxBookInfo a', 0)->getAttribute('href');
                $infop = $li->find('.boxBookInfo p');
                $item = [
                    'id' => $this->clean(strrchr($href, '/'), ['/record=', '*chx']),
                    'title' => $infop[0]->find('a')->text,
                    'picture' => $li->find('img', 0)->getAttribute('src'),
                    'counts' => intval(mb_substr($infop[1]->text, 5)),
                ];
                $items[] = $item;
            } catch(\Exception $e) {
                continue;
            }
        }
        return $items;
    }


    public function borrowings($borrower, $uid)
    {
        $args = [
            'name' => $borrower,
            'code' => $uid,
        ];
        $books = [];

        // follow 302 redirect and set cookie file to keep cookie in redirect request
        $html = $this->post('https://ftp.lib.hust.edu.cn/patroninfo*chx~S0~S0', $args, function(&$curl){
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setCookieFile(tmpfile());
        });

        $dom = $this->loadStr($html);

        // test if logged in successfully
        $login_msg = $dom->find('.loggedInMessage', 0);
        if (! $login_msg) return false;

        $rows = $dom->find('tr.patFuncEntry');

        // parse book rows
        try
        {
            foreach ($rows as $r) {
                $book = $this->_parseBorrowBookRow($r);
                if (! $book) continue;

                // parse expiration date
                $status = $book['status'];
                unset($book['status']);

                preg_match('/到期\s*([\d-]+)/', strip_tags($status), $ma);
                if (! $ma) continue;

                $book['expire_date'] = $ma[1];
                $books[] = $book;
            }
        }
        catch(\Exception $e)
        {
            return null;
        }


        return $books;
    }


    public function bookRenew($borrower, $uid, $rid)
    {
        $auth = [
            'name' => $borrower,
            'code' => $uid,
        ];

        $curl = $this->curl();
        $internal_uid = $this->_getInternalUserId($auth, $curl);
        if ( ! $internal_uid ) return false;

        // the ids of renewing books
        $rid = (array)$rid;
        $renew_ids = [];
        foreach ($rid as $k => $v) {
            $k += 1;
            $renew_ids["renew{$k}"] = $v;
        }

        // renew books
        $url = "https://ftp.lib.hust.edu.cn/patroninfo*chx/{$internal_uid}/items";
        $dom = $this->loadStr($curl->post($url, array_merge($auth, $renew_ids)));

        // parse results
        $results = [];
        $rows = $dom->find('tr.patFuncEntry');

        foreach ($rows as $r) {
            $book = $this->_parseBorrowBookRow($r);
            if (! $book || ! in_array($book['renew_id'], $rid)) continue;

            // parse renew result from status column
            $result = [
                'title' => $book['title'],
                'renew_id' => $book['renew_id'],
            ];
            $status = $book['status'];
            unset($book['status']);

            // test if renew action succeeded
            preg_match('/已续借\s*现在到期日为\s*([\d-]+)/', strip_tags($status), $ma);
            if ($ma) {
                $result['renewed'] = true;
                $result['expire_date'] = $ma[1];
            }
            else {
                $result['renewed'] = false;
                $err_node = $this->loadStr($status)->find('[color=red]', 0);
                $result['error'] = $err_node ?
                                 $this->clean(strip_tags($err_node->text)) : 'Unknown Error';
            }

            $results[] = $result;
        }

        $curl->close();

        return $results;
    }


    private function _parseBorrowBookRow($row)
    {
        $title_box = $row->find('.patFuncTitle');
        if (! $title_box) return null;
        $book = $this->_parseBookTitleBox($title_box);

        // try to get book internal renew id
        if ($rid = $row->find('.patFuncMark input', 0)) {
            $book['renew_id'] = $rid->getAttribute('value');
        }

        // expiration date
        $status_txt = $row->find('.patFuncStatus', 0)->innerHtml;
        $book['status'] = $status_txt;

        return $book;
    }


    public function borrowHistory($borrower, $uid, $pin)
    {
        $curl = $this->curl();
        $internal_uid = $this->_getInternalUserId([
            'name' => $borrower,
            'code' => $uid,
            'pin' => $pin,
        ], $curl);
        if ( ! $internal_uid ) return false;

        // get reading history page
        $url = "https://ftp.lib.hust.edu.cn/patroninfo*chx/{$internal_uid}/readinghistory";

        // parse borrow history from page
        $parseBorrowPage = function($page) {
            $dom = $this->loadStr($page);

            $items = [];
            $rows = $dom->find('.patFuncEntry');
            foreach ($rows as $r) {
                $items[] = $this->_parseBorrowHistory($r);
            }

            return $items;
        };

        $current_page = $curl->get($url);
        try
        {
            $books = $parseBorrowPage($current_page);

            // parse all borrow history from different pages
            $pagination = $this->loadStr($current_page)->find('td.browsePager', 0);
            if ($pagination) {
                $links = $pagination->find('a');
                for ($i = count($links) - 1; $i >= 0; $i--) {
                    preg_match('/^\d+$/', $this->clean($links[$i]->text), $ma);
                    if ($ma) {
                        $page = $curl->get("{$url}&page={$ma[0]}");
                        $books = array_merge($books, $parseBorrowPage($page));
                    }
                }
            }
        }
        catch(\Exception $e)
        {
            return null;
        }
        finally
        {
            $curl->close();
        }


        return $books;
    }


    private function _getInternalUserId($args, $curl=null) {
        $resp = $this->_loginBorrowPage($args, $curl);
        $headers = $resp->responseHeaders;

        // login failed, create pin
        if (! isset($headers['Location'])){
        	$args['pin1'] = $args['pin'];
        	$args['pin2'] = $args['pin'];
        	$resp = $this->_loginBorrowPage($args, $curl);
        	$headers = $resp->responseHeaders;
        }
             
        //login failed
        if (! isset($headers['Location'])) return null;
        
        preg_match('/chx\/(\d+)\//i', $headers['Location'], $ma);

        return $ma ? $ma[1] : null;
    }

    private function _loginBorrowPage($args, $curl=null)
    {
        $login_url = 'https://ftp.lib.hust.edu.cn/patroninfo*chx~S0~S0';

        if ( ! $curl) {
            $curl = new \Curl\Curl;
            $curl->setCookieFile(tmpfile());
        }
        
        $curl->post($login_url, $args);
        
        return $curl;
    }

    private function _parseBorrowHistory($row)
    {
        $borrow_date = $row->find('.patFuncDate', 0)->text;
        return array_merge(
            $this->_parseBookTitleBox($row),
            compact('borrow_date')
        );
    }


    private function _parseBookTitleBox($node)
    {
        // book title and author
        $title_line = $node->find('.patFuncTitleMain', 0)->text;
        list($title, $author) = array_map(function($e) {
            return $this->clean($e);
        }, explode(' / ', $title_line));

        // remove pinyin in title
        $pinyin_title = Pinyin::trans($title, ['accent' => false]);
        preg_match('/(.+?)\s*\1/', strtolower($pinyin_title), $ma);
        if ($ma) {
            $s = preg_quote($ma[1], '/');
            $title = preg_replace("/\s*{$s}[\s.]*/i", '', $title, 1);
        }

        // remove useless punctuations
        $rem = ['/\.\s*,\s*/', '/\s*[=.] =\s*/'];
        $title = preg_replace($rem, ' ', $title);
        $author = preg_replace($rem, ' ', $author);

        // book id
        $href = $node->find('a', 0)->getAttribute('href');
        $id = $this->clean(strrchr($href, '/'), ['/record=', '*chx']);

        return [
            'id' => $id,
            'title' => $title,
            'author' => $author,
        ];
    }


    public function bookInfo($id)
    {
        $dom = $this->loadUrl('http://ftp.lib.hust.edu.cn/record='.$id.'*chx');
        $info_table = $dom->find('#infoTable');

        // resolve book info
        $label_rows = $info_table->find('.bibInfoEntry tr');
        if (! count($label_rows)) return false;

        $label_maps = [
            '题名' => 'title',
            '作者名' => 'author',
            '出版发行' => 'publisher',
            'ISBN' => 'isbn',
        ];
        $info_items = [];

        foreach ($label_rows as $label_row) {
            try {
                $label = $this->clean($label_row->find('td.bibInfoLabel', 0)->text);

                if (isset($label_maps[$label])) {
                    $label_data = $label_row->find('td.bibInfoData', 0)->innerHtml;
                    $info_items[$label_maps[$label]] = $this->clean(strip_tags($label_data));
                }
            } catch(\Exception $e) {
                continue;
            }
        }

        // resolve book borrow info
        $stat_rows = $info_table->find('.bibDisplayItemsMain tr.bibItemsEntry');
        $stat_items = [];
        $desc = '';

        // if state row found
        if (count($stat_rows)) {
            foreach ($stat_rows as $stat_row) {
                $stat_items[] = [
                    'place' => $this->clean($stat_row->find('td', 0)->text),
                    'index' => $this->clean(strip_tags($stat_row->find('td', 1))),
                    'status' => $this->clean($stat_row->find('td', 2)->text),
                ];
            }
        } else {
            // if no state row found, just some description text
            $desc_box = $info_table->find('tr.bibOrderEntry', 0);
            if ($desc_box) {
                $desc = $this->clean(strip_tags($desc_box->innerHtml));
            }
        }

        // resolve book cover image
        $imgs = $dom->find('#revandRate img');
        foreach ($imgs as $img) {
            // if the src of image matches the specific segment of cover image
            $src = $img->getAttribute('src');

            if (stripos($src, 'zytest1.php?isbn=')) {
                $info_items['picture'] = $src;
                break;
            }
        }

        $info_items['collections'] = $stat_items;
        $info_items['description'] = $desc;

        return $info_items;
    }


    public function search($keywords)
    {
    	$curl = new Curl;
        $content = $curl->get('http://ftp.lib.hust.edu.cn/search*chx/X?SEARCH='.$keywords.'&SORT=D');
   
    	preg_match_all(
			'/<input\s+type="checkbox"\s+name="save"\s+value="([a-z0-9]+)\s?" >/iU', 
			$content, $ids);
    	preg_match_all(
			'/<span class="briefcitTitle">\s*<\s?[^>]*>(.*)\s*(?:\/|:|=)\s*([^<]*)</iU',
			 $content, $headers);
    	preg_match_all(
			'/<img src="([^?]*\?\s?isbn=\w*)"[^>]*>/iU',
			 $content, $pictures);
		
    	$items = [];
    	$i = 0;
    	foreach($ids[1] as $id){
    		$items[$i]['id'] = $ids[1][$i];
    		if($i > count($headers[1]) - 1){
	    		$items[$i]['title'] = $headers[1][count($headers[1]) - 1];
	    		$items[$i]['author'] = $headers[2][count($headers[1]) - 1];
    		}else{
    			$items[$i]['title'] = $headers[1][$i];
	    		$items[$i]['author'] = $headers[2][$i];
    		}
    		if($i > count($pictures[1]) - 1){
    			$items[$i]['picture'] = $pictures[1][count($pictures[1]) - 1];	
    		}else{
    			$items[$i]['picture'] = $pictures[1][$i];
    		}
    		$i ++;
    	}
    	return $items;
//        // do search
//        $dom = $this->loadUrl('http://ftp.lib.hust.edu.cn/search*chx/X?SEARCH='.$keywords.'&SORT=D');
//        // get result table
//        $table = $dom->find('body table.browseScreen');
//        if ( ! $table) return [];
//
//        // resolve rows
//        $rows = array_filter($table->find('tr td.briefCitRow')->toArray());
//        $items = [];
//        foreach ($rows as $row) {
//            if ($item = @$this->_parseRow($row)) {
//                $items[] = $item;
//            }
//        }
//
//        return $items;
    }


    public function searchStream($keywords, $handle=null, $end=null)
    {
        // stream file handler
        $f = fopen('http://ftp.lib.hust.edu.cn/search*chx/X?SEARCH='.$keywords.'&SORT=D', 'r');

        // mock a stack to store data chunk
        $stack = [
            'start_offset' => 0,
            'content' => '',
            'unclosed' => false,
        ];

        $rows = [];

        // disable nginx output buffering
        header('X-Accel-Buffering: no');

        $ended = false;
        // parse all chunks and call callback
        while($line = fgets($f)) {
            // try parse current stack content
            if ($raw_rows = $this->_parseChunk($line, $stack)) {
                foreach ($raw_rows as $v) {
                    // try parse raw row text
                    $row = @$this->_parseRow($this->loadStr($v));
                    if ( ! $row) continue;

                    $rows[] = $row;

                    if (! $ended AND $end AND is_callable($end) AND $end()) {
                        // mark client connection as finished
                        $ended = true;

                        // release client connection
                        if (function_exists('fastcgi_finish_request')){
                            fastcgi_finish_request();
                        }
                    }

                    // if connection is not finished then run callback
                    // to handle parsed data
                    if (! $ended AND $handle AND is_callable($handle)) {
                        $handle($row);

                        // flush output buffer
                        ob_flush();
                        flush();
                    }
                }
            }
        }

        return $rows;
    }


    private function _parseChunk($chunk, &$stack)
    {
        // append chunk to stack
        $stack['content'] .= (string)$chunk;

        // if no content then no need to continue parsing
        if ( ! $stack['content']) return null;

        // regexs to match row beginning/ending
        $pattern = [
            'start' => '<tr[^>]*?>\s*<td\s*class="[^"]*?briefCitRow[^"]*?">',
            'end' => '<\/table>\s*<\/td>\s*<\/tr>'
        ];

        $raw_rows = [];

        // if the stack contains no unclosed tag, then try to
        // match the start of a row
        if ( ! $stack['unclosed']) {
            if (preg_match('/('.$pattern['start'].')/ims',
                           $stack['content'],
                           $match,
                           PREG_OFFSET_CAPTURE)
            ) {
                $stack['start_offset'] = $match[1][1];
                $stack['unclosed'] = 1;
            }
        }

        // if the stack is marked as unclosed, then try to match a
        // whole row
        if ($stack['unclosed']) {
            if (preg_match('/('.$pattern['start'].').*?('.$pattern['end'].')/ims',
                           $stack['content'],
                           $match,
                           PREG_OFFSET_CAPTURE)
            ) {
                // row end offset
                $end_offset = $match[2][1] + strlen($match[2][0]);

                // add row text to collections
                $raw_rows[] = substr($stack['content'], $stack['start_offset'], $end_offset - $stack['start_offset']);

                // reset stack
                $stack['content'] = substr($stack['content'], $end_offset);
                $stack['unclosed'] = 0;

                // try parse the remaining string in the stack
                if ($rest = $this->_parseChunk(null, $stack)) {
                    $raw_rows = array_merge($rows, $rest);
                }
            }
        }

        return $raw_rows ?: null;
    }


    private function _parseRow($row)
    {
        $src_link = $row->find('td.briefcitActions a', 0)->getAttribute('onclick');
        $src_header = $row->find('td.briefcitDetail span.briefcitTitle a', 0)->text;

        // parse book id
        preg_match('/browse\?save=([a-z0-9]+)#/i', $src_link, $id);
        if ( ! ($id = @$id[1])) return;

        // parse book title
        preg_match('/(.*)\s*(?:\/|:|=)\s*(.*)/i', $src_header, $header);
        if ( ! ($title = @trim($header[1]) AND $author = @trim($header[2]))) {
            return;
        }

        // get picture url
        $pic = '';
        foreach ($row->find('td.briefcitExtras img') as $v) {
            $src = $v->getAttribute('src');
            if (stripos($src, 'zycover.php?isbn=')) {
                $pic = $src;
                break;
            }
        }
        if ( ! $pic) return;

        return [
            'id' => $id,
            'title' => $title,
            'author' => $author,
            'picture' => $pic,
        ];
    }
}
