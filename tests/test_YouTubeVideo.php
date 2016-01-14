<?php

use bermanco\YouTubeVideo\YouTubeVideo;
use Madcoda\Youtube;

class YouTubeVideoTest extends PHPUnit_Framework_TestCase {

	const TEST_API_KEY = 'AIzaSyDDefsgXEZu57wYgABF7xEURClu4UAzyB8';

	function setUp(){
		putenv('YOUTUBE_API_KEY=' . self::TEST_API_KEY);
	}

	function test_create(){

		$url = 'https://www.youtube.com/watch?v=pxk4YF46rsA';
		$yt = YouTubeVideo::create($url);
		$this->assertInstanceOf('bermanco\YouTubeVideo\YouTubeVideo', $yt);

	}

	/**
	 * @dataProvider youtube_url_id_provider
	 */
	function test_get_id_from_url_regex($id, $url){
		$yt = YouTubeVideo::create($url);
		$this->assertEquals($id, $yt->get_id_from_url_regex());
	}

	function youtube_url_id_provider(){

		return array(
			array("pxk4YF46rsA", "https://www.youtube.com/watch?v=pxk4YF46rsA"),
			array("ZV1Ho07AnXg", "https://youtu.be/ZV1Ho07AnXg"),
			array("VV0ozCoGTgs", "http://www.youtube.com/v/VV0ozCoGTgs?fs=1&hl=en_US"),
			array("-wtIMTCHWuI", "http://www.youtube.com/watch?v=-wtIMTCHWuI"),
			array("-wtIMTCHWuI", "http://www.youtube.com/v/-wtIMTCHWuI?version=3&autohide=1"),
			array("ZU6zDg3oYH4", "http://www.youtube.com/v/ZU6zDg3oYH4&hl=en_US&fs=1&"),
			array("-wtIMTCHWuI", "http://youtu.be/-wtIMTCHWuI"),
			array("ZU6zDg3oYH4", "http://www.youtube.com/v/ZU6zDg3oYH4&hl=en_US&fs=1&")
		);

	}

}
