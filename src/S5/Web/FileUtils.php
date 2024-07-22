<?
namespace S5\Web;

/**
 * Работа с пхпшным массивом $_FILES.
 */
class FilesUtils {
	private static $_fileDataKeysList = ['name', 'tmp_name', 'type', 'error', 'size'];

	/**
	 * Достаёт из массива $_FILES указанные данные во вменяемом виде.
	 * 
	 * Допустим, у нас на форме есть несколько файловых полей типа:
	 * ```
	 * <input type="file" name="div[subdiv][file][]">
	 * <input type="file" name="div[subdiv][file][]">
	 * <input type="file" name="div[subdiv][file][]">
	 * ```
	 * 
	 * Ожидаем, что после отправки формы значения полей можно будет получить как-то так:
	 * ```
	 * $_FILES['div']['subdiv']['file'][0]['name']; //1.txt
	 * $_FILES['div']['subdiv']['file'][0]['type']; //text/plain
	 * ```
	 * 
	 * А по всему списку переданных файлов пройти так:
	 * ```
	 * foreach ($_FILES['div']['subdiv']['file'] as $fileData) {
	 * 		echo $fileData['name']; //1.txt
	 * 		echo $fileData['type']; //text/plain
	 * }
	 * ```
	 * 
	 * Но не тут-то было. Пхп в этом случае производит массив дико упоротой структуры.
	 * Если посмотреть на него, то мы увидим:
	 * ```
	 * [
	 * 	'div' => [
	 * 		'name' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => '1.txt',
	 * 					1 => '2.txt',
	 * 					2 => '3.txt',
	 * 				],
	 * 			],
	 * 		],
	 * 		'type' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 'text/plain',
	 * 					1 => 'text/plain',
	 * 					2 => 'text/plain',
	 * 				],
	 * 			],
	 * 		],
	 * 		'tmp_name' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => '/tmp/phpglvlQp',
	 * 					1 => '/tmp/phpcXbzZv',
	 * 					2 => '/tmp/phpm3iN8B',
	 * 				],
	 * 			],
	 * 		],
	 * 		'error' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 0,
	 * 					1 => 0,
	 * 					2 => 0,
	 * 				],
	 * 			],
	 * 		],
	 * 		'size' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 1,
	 * 					1 => 1,
	 * 					2 => 1,
	 * 				],
	 * 			],
	 * 		],
	 * 	],
	 * ];
	 * ```
	 * 
	 * Лучше бы этого не видеть.
	 * 
	 * Код из первого примера ПХП предлагает писать так:
	 * ```
	 * $_FILES['div']['name']['subdiv']['file'][0]; //1.txt
	 * $_FILES['div']['type']['subdiv']['file'][0]; //text/plain
	 * ```
	 * 
	 * Код из второго примера - так:
	 * ```
	 * foreach (array_keys($_FILES['div']['name']['subdiv']['file']) as $index) {
	 * 		echo $_FILES['div']['name']['subdiv']['file'][$index]; //1.txt
	 * 		echo $_FILES['div']['type']['subdiv']['file'][$index]; //text/plain
	 * }
	 * ```
	 * 
	 * Или можно собрать свой массив с более удобным представлением данных:
	 * ```
	 * $files = [];
	 * foreach (['name', 'tmp_name', 'type', 'error', 'size'] as $fileDataKey) {
	 * 		foreach ($_FILES['div'][$fileDataKey]['subdiv']['file'] as $index => $value) {
	 * 			$files[$index][$fileDataKey] = $value;
	 * 		}
	 * }
	 * foreach ($files as $fileData) {
	 * 		echo $fileData['name']; //1.txt
	 * 		echo $fileData['type']; //text/plain
	 * }
	 * ```
	 * 
	 * Но это громоздко и нужно строчить каждый раз.
	 * 
	 * Как раз для сборки такого массива для структуры любого формата и предназначен этот метод.
	 * Что возвращает он:
	 * ```
	 * [
	 * 	0 => [
	 * 		'name'     => '1.txt',
	 * 		'tmp_name' => '/tmp/phpglvlQp',
	 * 		'type'     => 'text/plain',
	 * 		'error'    => 0,
	 * 		'size'     => 1,
	 * 	],
	 * 	1 => [
	 * 		'name'     => '2.txt',
	 * 		'tmp_name' => '/tmp/phpcXbzZv',
	 * 		'type'     => 'text/plain',
	 * 		'error'    => 0,
	 * 		'size'     => 1,
	 * 	],
	 * 	2 => [
	 * 		'name'     => '3.txt',
	 * 		'tmp_name' => '/tmp/phpm3iN8B',
	 * 		'type'     => 'text/plain',
	 * 		'error'    => 0,
	 * 		'size'     => 1,
	 * 	],
	 * ]
	 * ```
	 * 
	 * С его помощью пишем так:
	 * ```
	 * $files = FilesUtils::getFilesDataList(['div', 'subdiv', 'file']);
	 * $files[0]['name']; //1.txt
	 * $files[0]['type']; //text/plain
	 * 
	 * foreach ($files as $fileData) {
	 * 		echo $fileData['name']; //1.txt
	 * 		echo $fileData['type']; //text/plain
	 * }
	 * ```
	 */
	public static function getFilesDataList (array $fromKeysList): array {
		$data = [];
		array_splice($fromKeysList, 1, 0, '');
		foreach (static::$_fileDataKeysList as $fileDataKey) {
			$currentSource = $_FILES;
			$fromKeysList[1] = $fileDataKey;
			foreach ($fromKeysList as $fromKey) {
				$currentSource = $currentSource[$fromKey];
			}
			if (!is_array($currentSource)) {
				$data[$fileDataKey] = $currentSource;
			} else {
				foreach ($currentSource as $index => $fileDataItem) {
					$data[$index][$fileDataKey] = $fileDataItem;
				}
			}
		}
		return $data;
	}



	/**
	 * Превращает требуемую часть $_FILES из патологического хлама в удобоваримую структуру.
	 * 
	 * Суть почти такая же как у getFilesDataList(), только этот метод возвращает не одномерный список,
	 * а оригинальную вложенную структуру, приведённую во вменяемый вид.
	 * 
	 * То есть, вместо этого бреда:
	 * ```
	 * [
	 * 	'div' => [
	 * 		'name' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => '1.txt',
	 * 				],
	 * 			],
	 * 		],
	 * 		'type' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 'text/plain',
	 * 				],
	 * 			],
	 * 		],
	 * 		'tmp_name' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => '/tmp/phpglvlQp',
	 * 				],
	 * 			],
	 * 		],
	 * 		'error' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 0,
	 * 				],
	 * 			],
	 * 		],
	 * 		'size' => [
	 * 			'subdiv' => [
	 * 				'file' => [
	 * 					0 => 1,
	 * 				],
	 * 			],
	 * 		],
	 * 	],
	 * ]
	 * ```
	 * 
	 * Получаем вот так вот ожидаемо:
	 * ```
	 * [
	 * 	'div' => [
	 * 		'subdiv' => [
	 * 			'file' => [
	 * 				0 => [
	 * 					'name'     => '1.txt',
	 * 					'tmp_name' => '/tmp/phpglvlQp',
	 * 					'type'     => 'text/plain',
	 * 					'error'    => 0,
	 * 					'size'     => 1,
	 * 				],
	 * 			],
	 * 		],
	 * 	],
	 * ]
	 * ```
	 * 
	 * Вызов метода:
	 * ```
	 * $files = FilesUtils::getFilesDataTree('div', 'subdiv', 'file'));
	 * ```
	 * 
	 * @param  array $fromKeysList
	 * @return array
	 */
	public static function getFilesDataTree ($fromKeysList) {
		$data          = static::getFilesDataList($fromKeysList);
		$files         = [];
		$currentTarget = &$files;
		//Можно использовать более легко понимаемый подход с eval(),
		//но с ним работает в 15 раз медленнее.
		//Замеры времени:
		//float(0.12437701225281)
		//float(1.8892729282379)		
		foreach ($fromKeysList as $chainKey) {
			if (!isset($currentTarget[$chainKey])) {
				$currentTarget[$chainKey] = [];
			}
			$currentTarget = &$currentTarget[$chainKey];
		}
		$currentTarget = $data;
		return $files;
		//Для истории, вариант с eval().
		//$files = [];
		//$evalString = '$files';
		//foreach ($fromKeysList as $chainKey) {
		//	$evalString .= '['.$chainKey.']';
		//}
		//$evalString .= ' = $data;';
		//eval($evalString);
	}



	/**
	 * Выдача файла в браузер.
	 */
	public static function uploadFile (string $filePath) {
		if (!is_file($filePath) or !is_readable($filePath)) {
			throw new \Exception("Файл недоступен: $filePath");
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		header('Content-Type: ' . finfo_file($finfo, $filePath));
		finfo_close($finfo);

		header('Content-Disposition: attachment; filename='.basename($filePath));

		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . filesize($filePath));

		ob_clean();
		flush();
		readfile($filePath);
	}
}
