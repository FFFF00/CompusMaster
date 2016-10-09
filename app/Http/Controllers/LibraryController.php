<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Cache;
use App\Libraries\Fetcher;

class LibraryController extends Controller
{
    // return the hottest books
    public function hot(Request $req)
    {
        $cache_key = 'LIB:HOT:'.date('YmdA', time());
        $count = @intval($req->input('count', 10));
        $count = ($count > 0 AND $count <= 30) ? $count : 10;

        // try read cache
        if ($cache = Cache::get($cache_key, false)) {
            return $this->success(array_slice(unserialize($cache), 0, $count));
        }
        // fetch hot books from remote
        $items = Fetcher::get('Library')->hotBooks();

        // if items are valid then put into cache
        if ($items) {
            Cache::put($cache_key, serialize($items), 60 * 24);
        }

        return $this->success(array_slice($items, 0, $count));
    }


    // get borrowed books
    public function borrowings(Request $req)
    {
        $name = $req->input('name');
        $uid = $req->input('uid');

        $result = Fetcher::get('Library')->borrowings($name, $uid);
        if ($result === false) {
            return $this->fail(401, 'login failed.');
        }
        elseif ($result === null) {
            return $this->fail('failed to get borrowing books info.');
        }
        else {
            return $this->success($result);
        }
    }


    public function borrowHistory(Request $req)
    {
        $name = $req->input('name');
        $uid = $req->input('uid');

        $result = Fetcher::get('Library')->borrowHistory($name, $uid);
        if ($result === false) {
            return $this->fail(401, 'login failed.');
        }
        elseif ($result === null) {
            return $this->fail('failed to get borrowing histroy.');
        }
        else {
            return $this->success($result);
        }
    }


    public function bookRenew(Request $req)
    {
        $name = $req->input('name');
        $uid = $req->input('uid');
        $rid = explode(';', $req->input('rid'));

        $result = Fetcher::get('Library')->bookRenew($name, $uid, $rid);
        if ($result === false) {
            return $this->fail(401, 'login failed.');
        }
        else {
            return $this->success($result);
        }
    }


    // search keywords
    public function search(Request $req)
    {
        // get search keywords
        $keywords = urlencode(trim($req->input('keywords')));
        // return how many rows
        $count = @intval($req->input('count', 10));
        $count = ($count > 0 AND $count <= 30) ? $count : 10;

        // whether use stream parser
        $stream = $req->input('stream');

        $cache_key = "LIB:SEARCH:".$keywords;

        // if no keywords specificed, return hot books
        if ( ! $keywords) {
            return $this->hot($req);
        }

        // if cache available then return directly
        if ($cache = Cache::get($cache_key, false)) {
            $data = array_slice(unserialize($cache), 0, $count);

            if ( ! $stream) {
                return $this->success($data);
            } else {
                foreach($data as $v) {
                    echo json_encode($v)."\n";
                }
                return;
            }
        }

        // whether use stream parser
        if ( ! $stream) {
            $items = Fetcher::get('Library')->search($keywords);
        } else {
            // fetch search result from remote
            $i = 1;
            $items = Fetcher::get('Library')->searchStream(
                $keywords,
                function($data) {
                    echo json_encode($data)."\n";
                },
                function() use (&$i, &$count) {
                    return $i++ > $count;
                }
            );
        }

        // if items are valid then push into cache
        //$items AND Cache::put($cache_key, serialize($items), 60 * 24);

        if ( ! $stream) {
            return $items ? $this->success(array_slice($items, 0, $count)) : $this->fail();
        }
    }


    // show the specific book
    public function info(Request $req, $id)
    {
        $cache_key = 'LIB:BOOK:'.$id;

        // check if cache exists
        if ($cache = Cache::get($cache_key, false)) {
            return $this->success(unserialize($cache));
        }

        $info = Fetcher::get('Library')->bookInfo($id);
        if ($info) {
            Cache::put($cache_key, serialize($info), 30);

            return $this->success($info);
        }

        return $this->fail();
    }
}
