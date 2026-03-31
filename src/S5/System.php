<?
namespace S5;



class System {
	public static function exec (string $command, bool $isThrowException = true): array {
		$output = '';
		$code   = 0;

		ob_start();
		exec($command.' 2>&1', $output, $code);
		ob_end_clean();

		$r = compact('code', 'output');
		if ($r['code'] and $isThrowException) {
			throw new \Exception(
				"Не удалось выполнить команду:\n$command\n----------\n" .
				join("\n", $r['output'])
			);
		}

		return $r;
	}



	public static function passthru (string $command, bool $isThrowException = true): int {
		passthru($command.' 2>&1', $code);

		if ($code and $isThrowException) {
			throw new \Exception("Не удалось выполнить команду:\n$command");
		}

		return $code;
	}
}
