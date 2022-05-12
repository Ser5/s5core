<?
namespace S5;

class TextUtils {
	/**
	 * Обрезает текст до необходимой длины так, что на конце остаётся целое слово.
	 */
	public static function cutByWords (string $initialText, int $length, string $appendTail = '&hellip;'): string {
		$text = strip_tags($initialText);
		if (mb_strlen($text, $encoding) > $length) {
			$text = mb_substr($text, 0, $length+1, 'UTF-8');
			self::_cleanTail($text);
			$text .= $appendTail;
			return $text;
		} else {
			return $text;
		}
	}

	private static function _cleanTail (&$text) {
		$last_space_pos = mb_strrpos($text, ' ', 'UTF-8');
		$text           = mb_substr($text, 0, $last_space_pos, 'UTF-8');
	}
}
