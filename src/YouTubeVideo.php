<?php

namespace bermanco\YouTubeVideo;
use Madcoda\Youtube;

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

		$id = $this->get_id();

		$video_data = $this->youtube_api->getVideoInfo($id);

		if ($video_data){

			$this->video_data = $video_data;
			return $video_data;

		}

	}

	/**
	 * Extract the video's ID from its URL
	 * @return string|null YouTube video ID
	 */
	public function get_id(){

		$video_id = $this->youtube_api->parseVIdFromURL(
			$this->url
		);

		if ($video_id){
			return $video_id;
		}

	}

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
	public function get_embed($css_classes = '', $attributes = array()){

		$default_attributes = array(
			'frameborder' => '0',
			'allowfullscreen'
		);

		$attributes = $default_attributes + $attributes;

		$video_data = $this->get_data();

		if ($this->get_id()){

			$src = "https://www.youtube.com/embed/{$this->get_id()}?modestbranding=1;controls=1;showinfo=0;rel=0;fs=1";

			$attributes = $this->generate_html_attributes($attributes);

			$iframe = "<iframe class='$css_classes' src='$src' $attributes></iframe>";

			return $iframe;

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

	public static function create($url){

		$api_key = getenv('YOUTUBE_API_KEY');

		if ($api_key){

			$youtube_api = new Youtube(array(
				'key' => $api_key
			));

			return new self($youtube_api, $url);

		}
		else {
			throw new \Exception('Missing YouTube API key');
		}

	}

}


