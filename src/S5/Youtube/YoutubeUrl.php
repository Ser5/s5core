<?
namespace S5\Youtube;

/**
 * Манипуляции с урлами ютуба.
 */
class YoutubeUrl {
	protected string $urlString;
	protected string $videoId;

	public function __construct (string $urlString) {
		$this->urlString = $urlString;

		static $methodNamesList = ['_getYoutubeComWatchId', '_getYoutuBeId', '_getEmbedId'];
		$id = false;
		foreach ($methodNamesList as $methodName) {
			if ($id = $this->$methodName()) {
				break;
			}
		}
		if (!$id) {
			throw new \InvalidArgumentException("Не является ссылкой youtube: [$urlString]");
		}
		$this->videoId = $id;
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
			$return = $this->urlString;
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
			$return = $this->urlString;
		}
		return $return;
	}



	public function getVideoId (): string {
		return $this->videoId;
	}



	/** @return string|false */
	protected function _getYoutubeComWatchId () {
		parse_str(parse_url($this->urlString, PHP_URL_QUERY), $varsHash);
		if (strpos($this->urlString, '/watch') and isset($varsHash['v'])) {
			return $varsHash['v'];
		} else {
			return false;
		}
	}

	/** @return string|false */
	protected function _getYoutuBeId () {
		$matches = [];
		if (preg_match('|youtu\.be/(.+)$|', $this->urlString, $matches)) {
			return $matches[1];
		} else {
			return false;
		}
	}

	/** @return string|false */
	protected function _getEmbedId () {
		$matches = [];
		if (preg_match('|/embed/(.+)$|', $this->urlString, $matches)) {
			return $matches[1];
		} else {
			return false;
		}
	}
}
