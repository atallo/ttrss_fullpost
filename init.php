<?php
class Af_Full extends Plugin {
	private $host;

	function about() {
		return array(0.99,
			"Full post (requires CURL)",
			"atallo");
	}

	function init($host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
		$host->add_hook($host::HOOK_PREFS_EDIT_FEED, $this);
		$host->add_hook($host::HOOK_PREFS_SAVE_FEED, $this);
	}

	function hook_prefs_edit_feed($feed_id) {
		print "<div class=\"dlgSec\">".__("Plugin (full_post)")."</div>";
		print "<div class=\"dlgSecCont\">";
		$enabled_feeds = $this->host->get($this, "enabled_feeds");
		if (!array($enabled_feeds)) $enabled_feeds = array();
		$key = array_search($feed_id, $enabled_feeds);
		$checked = $key !== FALSE ? "checked" : "";
		print "<hr/><input dojoType=\"dijit.form.CheckBox\" type=\"checkbox\" id=\"affull_enabled\"
			name=\"affull_enabled\"
			$checked>&nbsp;<label for=\"affull_enabled\">".__('Show full post')."</label>";
		print "</div>";
	}

	function hook_prefs_save_feed($feed_id) {
		$enabled_feeds = $this->host->get($this, "enabled_feeds");
		if (!is_array($enabled_feeds)) $enabled_feeds = array();
		$enable = checkbox_to_sql_bool($_POST["affull_enabled"]) == 'true';
		$key = array_search($feed_id, $enabled_feeds);
		if ($enable) {
			if ($key === FALSE) {
				array_push($enabled_feeds, $feed_id);
			}
		} else {
			if ($key !== FALSE) {
				unset($enabled_feeds[$key]);
			}
		}
		$this->host->set($this, "enabled_feeds", $enabled_feeds);
	}

	function hook_article_filter($article) {
		if (!function_exists("curl_init"))
			return $article;

		$enable_globally = $this->host->get($this, "enable_globally");
		if (!$enable_globally) {
			$enabled_feeds = $this->host->get($this, "enabled_feeds");
			$key = array_search($article["feed"]["id"], $enabled_feeds);
			if ($key === FALSE) return $article;
		}

		$article["content"] .= "<br/><hr/>Full post:<br><hr/>" .
			$this->get_full_post($article["link"]);

		return $article;
	}

	function api_version() {
		return 2;
	}

	private function get_full_post($request_url) {
		// now an amalgamation of code from:
		//   1) https://github.com/feelinglucky/php-readability
		//   2) http://code.fivefilters.org/php-readability/src
		include_once 'Readability.php';

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

		$html = curl_exec($handle);
		curl_close($handle);

		//if (!$charset = mb_detect_encoding($source)) {
		//}
		preg_match("/charset=([\w|\-]+);?/", $html, $match);
		$charset = isset($match[1]) ? $match[1] : 'utf-8';
		$html = mb_convert_encoding($html, 'UTF-8', $charset);

		// If we've got Tidy, let's clean up input.
		// This step is highly recommended - PHP's default HTML parser often doesn't do a great job and results in strange output.
		if (function_exists('tidy_parse_string')) {
			$tidy = tidy_parse_string($html, array(), 'UTF8');
			$tidy->cleanRepair();
			$html = $tidy->value;
		}

		$readability = new Readability($html);
		// print debug output?
		$readability->debug = false;
		// convert links to footnotes?
		$readability->convertLinksToFootnotes = false;
		$result = $readability->init();

		if ($result) {
			// $title = $readability->getTitle()->textContent;
			$content = $readability->getContent()->innerHTML;
			// if we've got Tidy, let's clean it up for output
			if (function_exists('tidy_parse_string')) {
				$tidy = tidy_parse_string($content, array('indent'=>true, 'show-body-only' => true), 'UTF8');
				$tidy->cleanRepair();
				$content = $tidy->value;
			}
		} else {
			# Raise an error so that we know not to replace the RSS stub article with something even less helpful
			throw new Exception('Full-text extraction failed');
		}

		return $content;
	}

	private function get_full_post_old($request_url) {
		include_once 'Readability.inc.php';

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

		preg_match("/charset=([\w|\-]+);?/", $source, $match);
		$charset = isset($match[1]) ? $match[1] : 'utf-8';

		try {
			$Readability = new Readability($source, $charset);
			$Data = $Readability->getContent();

			return $Data['content'];
		} catch (Exception $e) {
			return 'Readability: Error sacando datos';
		}
	}

}
?>
