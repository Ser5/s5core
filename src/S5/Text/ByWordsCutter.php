<?
namespace S5\Text;

class ByWordsCutter {
	/**
	 * Обрезает текст до необходимой длины так, что на конце остаётся целое слово.
	 */
	public static function cut (string $initialText, int $length, string $appendTail = '&hellip;'): string {
		$text = strip_tags($initialText);
		if (mb_strlen($text, 'UTF-8') > $length) {
			$text  = mb_substr($text, 0, $length+1, 'UTF-8');
			$text  = static::cleanTail($text);
			$text .= $appendTail;
			return $text;
		} else {
			return $text;
		}
	}

	protected static function cleanTail (string $text): string {
		$lastSpacePos = mb_strrpos($text, ' ', 0, 'UTF-8');
		$text         = mb_substr($text, 0, $lastSpacePos, 'UTF-8');
		return $text;
	}
}
