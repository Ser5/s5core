<?
require_once 'Net/Url2.php';
require_once 'S5/Net/Url2.php';
require_once 'S5/Youtube/Url.php';
require_once 'PHPUnit/Framework/TestCase.php';

class S5_Youtube_UrlTest extends \PHPUnit\Framework\TestCase {
	private static $_youtubeComUrlString = 'http://www.youtube.com/watch?v=abcdef';
	private static $_youtubeBeUrlString  = 'http://www.youtu.be/abcdef';
	private static $_embedUrlString      = '//www.youtube.com/embed/abcdef';

	public function testGettingWatchAndEmbedUrls () {
		//youtube.com
		$url = new S5_Youtube_Url(self::$_youtubeComUrlString);
		$this->assertEquals(self::$_youtubeComUrlString, $url->getWatchUrlString());
		$this->assertEquals(self::$_embedUrlString,      $url->getEmbedUrlString());
		//youtu.be
		$url = new S5_Youtube_Url(self::$_youtubeBeUrlString);
		$this->assertEquals(self::$_youtubeBeUrlString, $url->getWatchUrlString());
		$this->assertEquals(self::$_embedUrlString,     $url->getEmbedUrlString());
		//embed
		$url = new S5_Youtube_Url(self::$_embedUrlString);
		$this->assertEquals(self::$_youtubeComUrlString, $url->getWatchUrlString());
		$this->assertEquals(self::$_embedUrlString,      $url->getEmbedUrlString());
	}



	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct () {
		new S5_Youtube_Url('bubube.com/wach=122');
	}



	public function testGetVideoId () {
		//youtube.com
		$url = new S5_Youtube_Url(self::$_youtubeComUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
		//youtu.be
		$url = new S5_Youtube_Url(self::$_youtubeBeUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
		//embed
		$url = new S5_Youtube_Url(self::$_embedUrlString);
		$this->assertEquals('abcdef', $url->getVideoId());
	}
}
