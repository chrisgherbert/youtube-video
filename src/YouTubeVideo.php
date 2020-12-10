<?php

namespace chrisgherbert\YouTubeVideo;
use Madcoda\Youtube\Youtube;

class YouTubeVideo {

	protected $youtube_api;
	protected $url;
	public $video_data;

	public function __construct(Youtube $youtube_api, $url){
		$this->youtube_api = $youtube_api;
		$this->url = $url;
	}

	/**
	 * Fetch YouTube video info using the YouTube Data API
	 * @return StdClass|null Video data
	 */
	public function get_data(){

		if ($this->video_data){
			return $this->video_data;
		}

		$id = $this->get_id_from_url_regex();

		$video_data = $this->youtube_api->getVideoInfo($id);

		if ($video_data){

			$this->video_data = $video_data;
			return $video_data;

		}

	}

	/**
	 * Extract the video's ID from its URL using YouTube::parseVidFromURL.
	 * This method doesn't work very consistently, so it's usually better to
	 * use the get_id_from_url_regex method below
	 * @return string|null YouTube video ID
	 */
	public function get_id_from_madcoda(){

		try {
			$video_id = $this->youtube_api->parseVIdFromURL(
				$this->url
			);
		}
		catch (Exception $e){
			error_log("Caught exception: " . $e->getMessage());
		}

		if (isset($video_id) && $video_id){
			return $video_id;
		}

	}

	/**
	 * Adapted from http://stackoverflow.com/a/5831191/1667136
	 * @return string YouTube video ID
	 */
	function get_id_from_url_regex() {

		$text = $this->url;

	    $text = preg_replace('~
	        # Match non-linked youtube URL in the wild. (Rev:20130823)
	        https?://         # Required scheme. Either http or https.
	        (?:[0-9A-Z-]+\.)? # Optional subdomain.
	        (?:               # Group host alternatives.
	          youtu\.be/      # Either youtu.be,
	        | youtube         # or youtube.com or
	          (?:-nocookie)?  # youtube-nocookie.com
	          \.com           # followed by
	          \S*             # Allow anything up to VIDEO_ID,
	          [^\w\s-]       # but char before ID is non-ID char.
	        )                 # End host alternatives.
	        ([\w-]{11})      # $1: VIDEO_ID is exactly 11 chars.
	        (?=[^\w-]|$)     # Assert next char is non-ID or EOS.
	        (?!               # Assert URL is not pre-linked.
	          [?=&+%\w.-]*    # Allow URL (query) remainder.
	          (?:             # Group pre-linked alternatives.
	            [\'"][^<>]*>  # Either inside a start tag,
	          | </a>          # or inside <a> element text contents.
	          )               # End recognized pre-linked alts.
	        )                 # End negative lookahead assertion.
	        [?=&+%\w.-]*        # Consume any URL (query) remainder.
	        ~ix',
	        '$1',
	        $text);

		if ($text){
			return $text;
		}

	}

	////////////////////
	// Simple Getters //
	////////////////////

	public function get_views(){

		$data = $this->get_data();

		if (isset($data->statistics->viewCount)){
			return $data->statistics->viewCount;
		}

	}

	public function get_likes(){

		$data = $this->get_data();

		if (isset($data->statistics->likeCount)){
			return $data->statistics->likeCount;
		}

	}

	public function get_dislikes(){

		$data = $this->get_data();

		if (isset($data->statistics->dislikeCount)){
			return $data->statistics->dislikeCount;
		}

	}

	public function get_favorites(){

		$data = $this->get_data();

		if (isset($data->statistics->favoriteCount)){
			return $data->statistics->favoriteCount;
		}

	}

	///////////////////////////////////
	// Slightly More Complex Getters //
	///////////////////////////////////

	/**
	 * Get the URL of the video's largest thumbnail
	 * @return string|null URL of the largest thumbnail
	 */
	public function get_largest_thumbnail_url(){

		$largest_thumbnail = $this->get_largest_thumbnail();

		if ($largest_thumbnail){
			return $largest_thumbnail->url;
		}

	}

	/**
	 * Get the video's largest thumbnail (by pixel count)
	 * @return StdClass|null Object containing info on largest thumbnail
	 */
	public function get_largest_thumbnail(){

		$thumbnails = $this->get_thumbnails();

		if ($thumbnails){

			$sorted = array();

			foreach ($thumbnails as $key => $thumbnail){

				$pixel_area = $thumbnail->width * $thumbnail->height;

				$sorted[$pixel_area] = $key;

			}

			krsort($sorted);

			reset($sorted);

			$largest_thumbnail_key = current($sorted);

			return $thumbnails[$largest_thumbnail_key];

		}

	}

	/**
	 * Get an array of video thumbnails
	 * @return array|null Video thumbnails array
	 */
	public function get_thumbnails(){

		$video_data = $this->get_data();

		if ($video_data && isset($video_data->snippet->thumbnails)){

			return (array) $video_data->snippet->thumbnails;

		}

	}

	/**
	 * Create an iframe embed of the YouTube video
	 * @param  string $css_classes CSS classes to be added to the iframe element
	 * @param  array  $attributes  HTML attributes
	 * @return string|null         iFrame embed code
	 */
	public function get_embed($css_classes = '', $attributes = array(), $url_params = "modestbranding=1;controls=1;showinfo=0;rel=0;fs=1"){

		$default_attributes = array(
			'frameborder' => '0',
			'allowfullscreen'
		);

		$attributes = $default_attributes + $attributes;

		$src = $this->get_embed_url();

		if ($src){

			$attributes = $this->generate_html_attributes($attributes);

			$iframe = "<iframe class='$css_classes' src='$src' $attributes></iframe>";

			return $iframe;

		}

	}

	/**
	 * Create a YouTube embed URL
	 * @param  string      $url_params YouTube URL parameters for embed code
	 * @return string|null YouTube embed URL
	 */
	public function get_embed_url($url_params = "modestbranding=1;controls=1;showinfo=0;rel=0;fs=1"){

		$id = $this->get_id_from_url_regex();

		if ($id){

			$url = "https://www.youtube.com/embed/$id";

			if ($url_params){
				$url .= "?$url_params";
			}

			return $url;

		}

	}

	///////////////
	// Protected //
	///////////////

	/**
	 * Create HTML attributes string from array.  Each array item can either
	 * be the complete attribute string ("data-test='1234'") or a key with the
	 * attribute name and the value of the attribute value ("data-test" => "1234")
	 * @param  array $attributes Array of HTML attributes
	 * @return string            HTML attributes string, ready to be inserted
	 *                           into element markup
	 */
	protected function generate_html_attributes(array $attributes){

		$output = array();

		if ($attributes){

			foreach ($attributes as $key => $attribute){

				if (!is_numeric($key)){
					$output[] = "$key='$attribute'";
				}
				else {
					$output[] = $attribute;
				}

			}

			return implode(' ', $output);

		}

	}

	/////////////
	// Factory //
	/////////////

	public static function create($api_key, $url){

		$youtube_api = new Youtube(array(
			'key' => $api_key
		));

		return new self($youtube_api, $url);

	}

}
