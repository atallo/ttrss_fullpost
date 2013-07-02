<?php

//
// More info about this plugin in: https://github.com/atallo/ttrss_fullpost/
//
class Af_Full extends Plugin {
	private $host;

	function about() {
		return array(0.01,
			"Full post (requires CURL). (Work in progress)",
			"atallo");
	}

	function init($host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
	}

	function hook_article_filter($article) {
		if (!function_exists("curl_init"))
			return $article;

		if ((strpos($article["link"], "theinquirer.es") !== FALSE ||
			strpos($article["link"], "silicon-news") !== FALSE ||
			strpos($article["link"], "siliconnews") !== FALSE
		)) {

		    $article["content"] .= "<br/><hr/>Web original:<br><hr/>" .
		        $this->get_full_post($article["link"]);
		}

		return $article;
	}

	function api_version() {
		return 2;
	}

	private function get_full_post($request_url) {
	    // https://github.com/feelinglucky/php-readability#

	    include_once 'Readability.inc.php';

	    //define("DIR_ROOT", dirname(__FILE__));
	    //define("DIR_CACHE",  DIR_ROOT . '/cache');

	    //$request_url = "http://www.siliconnews.es/2013/07/01/estos-son-los-sueldos-de-los-principales-directivos-de-la-industria-tecnologica/";

	    //$request_url_hash = md5($request_url);
	    //$request_url_cache_file = sprintf(DIR_CACHE."/%s.url", $request_url_hash);

	    //if (file_exists($request_url_cache_file) &&
	    //    (time() - filemtime($request_url_cache_file) < CACHE_TIME)) {
            //
	    //    $source = file_get_contents($request_url_cache_file);
            //} else {

                $handle = curl_init();
                curl_setopt_array($handle, array(
                    CURLOPT_USERAGENT => USER_AGENT,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HEADER  => false,
                    CURLOPT_HTTPGET => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_URL => $request_url
                ));

                $source = curl_exec($handle);
                curl_close($handle);

                // Write request data into cache file.
            //    file_put_contents($request_url_cache_file, $source);
            //}

            //if (!$charset = mb_detect_encoding($source)) {
            //}
            preg_match("/charset=([\w|\-]+);?/", $source, $match);
            $charset = isset($match[1]) ? $match[1] : 'utf-8';

            $Readability = new Readability($source, $charset);
            $Data = $Readability->getContent();

            //$title   = $Data['title'];
            //$content = $Data['content'];

            //#include 'template/reader.html';
            //echo $title;

            return $Data['content'];
        }

}
?>
