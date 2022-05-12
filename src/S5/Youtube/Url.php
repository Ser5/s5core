<?
namespace S5\Youtube;

/**
 * Манипуляции с урлами ютуба.
 */
class Url {
	private string $_urlString;
	private string $_videoId;

	public function __construct (string $urlString) {
		$this->_urlString = $urlString;

		static $methodNamesList = array('_getYoutubeComWatchId', '_getYoutuBeId', '_getEmbedId');
		$id = false;
		foreach ($methodNamesList as $methodName) {
			if ($id = $this->$methodName()) {
				break;
			}
		}
		if (!$id) {
			throw new \InvalidArgumentException("Not an youtube url");
		}
		$this->_videoId = $id;
	}



	/**
	 * Возвращает URL для просмотра.
	 *
	 * URL может быть возвращён в двух видах:
	 * - http://www.youtube.com/watch?v=xxxxxx
	 * - http://www.youtu.be/xxxxxx
	 */
	public function getWatchUrlString (): string {
		if ($id = $this->_getEmbedId()) {
			$return = 'http://www.youtube.com/watch?v='.$id;
		} else {
			$return = $this->_urlString;
		}
		return $return;
	}



	/**
	 * Возвращает URL для встраивания, вида //www.youtube.com/embed/xxxxxx
	 */
	public function getEmbedUrlString (): string {
		if ($id = $this->_getYoutubeComWatchId()) {
			$return = '//www.youtube.com/embed/'.$id;
		} elseif ($id = $this->_getYoutuBeId()) {
			$return = '//www.youtube.com/embed/'.$id;
		} else {
			$return = $this->_urlString;
		}
		return $return;
	}



	public function getVideoId (): string {
		return $this->_videoId;
	}



	/** @return string|false */
	private function _getYoutubeComWatchId () {
		parse_str(parse_url($this->_urlString, PHP_URL_QUERY), $varsHash);
		if ($url->getPath() == '/watch' and isset($varsHash['v'])) {
			return $varsHash['v'];
		} else {
			return false;
		}
	}

	/** @return string|false */
	private function _getYoutuBeId () {
		$matches = [];
		if (preg_match('|youtu\.be/(.+)$|', $this->_urlString, $matches)) {
			return $matches[1];
		} else {
			return false;
		}
	}

	/** @return string|false */
	private function _getEmbedId () {
		$matches = [];
		if (preg_match('|/embed/(.+)$|', $this->_urlString, $matches)) {
			return $matches[1];
		} else {
			return false;
		}
	}
}
