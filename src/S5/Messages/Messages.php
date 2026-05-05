<?
namespace S5\Messages;

use S5\Text\Lang;



class Messages {
	protected Lang $lang;

	/** @var array<string, string|\Closure> */
	public array $messagesMap;



	public function __construct (
		Lang|string|null $lang = null,
	) {
		if (is_object($lang)) {
			$this->lang = $lang;
		} elseif (is_string($lang)) {
			$this->lang = new Lang($lang);
		} else {
			$this->lang = new Lang('ru');
		}
	}



	public function get (string $key, ?array $vars = null): string {
		if (is_string($this->messagesMap[$key])) {
			$message = $this->messagesMap[$key];
		} else {
			$message = ($this->messagesMap[$key])($vars);
		}

		if (str_contains($message, '{')) {
			$message = $this->lang->format($message, $vars ?? []);
		}

		return $message;
	}
}
