<?
namespace S5\Youtube;

class YoutubeUrlTest extends \S5\TestCase {
	private static $_youtubeComUrlString = 'http://www.youtube.com/watch?v=abcdef';
	private static $_youtubeBeUrlString  = 'http://www.youtu.be/abcdef';
	private static $_embedUrlString      = '//www.youtube.com/embed/abcdef';

	public function testGettingWatchAndEmbedUrls () {
		//youtube.com
		$url = new YoutubeUrl(static::$_youtubeComUrlString);
		$this->assertEquals(static::$_youtubeComUrlString, $url->getWatchUrlString());
		$this->assertEquals(static::$_embedUrlString,      $url->getEmbedUrlString());
		//youtu.be
		$url = new YoutubeUrl(static::$_youtubeBeUrlString);
		$this->assertEquals(static::$_youtubeBeUrlString, $url->getWatchUrlString());
		$this->assertEquals(static::$_embedUrlString,     $url->getEmbedUrlString());
		//embed
		$url = new YoutubeUrl(static::$_embedUrlString);
		$this->assertEquals(static::$_youtubeComUrlString, $url->getWatchUrlString());
		$this->assertEquals(static::$_embedUrlString,      $url->getEmbedUrlString());
	}



	public function testInvalidConstruct () {
		$this->expectException(\InvalidArgumentException::class);
		new YoutubeUrl('bubube.com/wach=122');
	}



	public function testGetVideoId () {
		//youtube.com
		$url = new YoutubeUrl(static::$_youtubeComUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
		//youtu.be
		$url = new YoutubeUrl(static::$_youtubeBeUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
		//embed
		$url = new YoutubeUrl(static::$_embedUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
	}
}
