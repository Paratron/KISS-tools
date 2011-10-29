<?php
/**
 * Essencial Twitter functions
 * @autor Christian Engel <christian.engel@wearekiss.com>
 * @version 1 16.10.2011
 */
class kTwitter {
    /**
     * How long should search results be cached? In seconds.
     * @var int
     */
    var $cache_time = 10;

    /**
     * The folder to put cache files into.
     * Must obviously be writeable ;)
     * @var string
     */
    var $cache_folder = 'lib/php/cache/';

    /**
     * Sends a query to the twitter search and returns the result.
     * Performs some functions to render a better timestamp.
     * Queries will be cached for the defined $cache_time
     * @param string $query
     * @return array
     */
    function search($query){
        if($result = $this->cache_load($query)){
            return $result;
        }

        error_reporting(FALSE);
        $result = json_decode(file_get_contents('http://search.twitter.com/search.json?q='.urlencode($query)), TRUE);
        if($result == FALSE){
            return $this->cache_load($query, TRUE);
        }
        foreach($result['results'] as $k=>$v){
            $result['results'][$k]['time'] = strtotime($v['created_at']);
            $result['results'][$k]['rel_time'] = $this->nice_time_format(strtotime($v['created_at']));
        }
        $results['issued_at'] = time();

        $this->cache_write($query, $result);
        return $result;
    }

    /**
     * This function takes an array of tweets either from search() or from_user() and filters the spam out of it.
     * @param array $tweet_array
     * @return array
     */
    function filter($tweet_array){
        $result = array();
        $filterstrings = array(
            'If you girls take care',
            'BlacksGmgtastic',
            'you don\'t like me',
            'FUCKING',
            'PiegonsAndDucks',
            'agora uma vez por seculo'
        );
        foreach($tweet_array['results'] as $v){
            $break = FALSE;
            foreach($filterstrings as $f){
                if(strpos($v['text'], $f) !== FALSE){
                    $break = TRUE;
                    continue;
                }
            }
            if(!$break) $result[] = $v;
        }
        $tweet_array['results'] = $result;
        return $tweet_array;
    }

    function from_user($username){
        if($result = $this->cache_load('user_'.$username)){
            return $result;
        }

        error_reporting(FALSE);
        $result = json_decode(file_get_contents('http://search.twitter.com/search.json?from='.urlencode($username)), TRUE);
        if($result == FALSE){
            return $this->cache_load('user_'.$username, TRUE);
        }
        foreach($result['results'] as $k=>$v){
            $result['results'][$k]['time'] = strtotime($v['created_at']);
            $result['results'][$k]['rel_time'] = $this->nice_time_format(strtotime($v['created_at']));
        }
        $results['issued_at'] = time();

        $this->cache_write('user_'.$username, $result);
        return $result;
    }

    /**
     * This function takes a timestamp and formats it nicely. :)
     * @param $timestamp
     * @return string
     */
    function nice_time_format($timestamp){
        $difference = time() - $timestamp;

        if($difference < 60) return 'a few seconds ago';
        if($difference > 59 && $difference < 120) return 'one minute ago';
        if($difference > 119 && $difference < 60*60) return round($difference / 60).' minutes ago';
        if($difference >= 60*60 && $difference < 86400) return round($difference / 3600).' hours ago';
        if($difference >= 86400 && $difference < 172800) return 'yesterday';
        if($difference > 172800) return date('jS M Y', $timestamp);
    }

    /**
     * Writes data into a cache file.
     * The identifier string will be turned into an MD5 Hash and used as cache file name.
     * @param string $identifier
     * @param mixed $data
     * @return void
     */
    function cache_write($identifier, $data){
        $file = $this->cache_folder.'twitter_'.md5($identifier);
        file_put_contents($file, serialize($data));
    }

    /**
     * Tries to load a cache file.
     * If the cache doesnt exist or is invalid, the function will return FALSE.
     * @param string $identifier
     * @return mixed|FALSE
     */
    function cache_load($identifier, $force = FALSE){
        $file = $this->cache_folder.'twitter_'.md5($identifier);
        if(!file_exists($file)) return FALSE;
        if(!$force){
            if(filemtime($file) < time()-$this->cache_time) return FALSE;
        }

        return unserialize(file_get_contents($file));
    }
}
