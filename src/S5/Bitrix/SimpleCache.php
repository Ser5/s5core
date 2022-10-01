<?
namespace S5\Bitrix;

/**
 * Упрощённая работа с кэшем CPHPCache буга.
 *
 * Как выглядит кэширование в буге, если используются тэги:
 * ```
 * global $CACHE_MANAGER;
 * $cache = new \CPHPCache();
 * if ($cache->InitCache($cacheTime, $cacheId, $cacheDir)) {
 *    $vars = $cache->GetVars();
 * } else {
 *    $vars = getMyVars();
 *    $cache->StartDataCache();
 *    $CACHE_MANAGER->StartTagCache($cacheDir);
 *       $CACHE_MANAGER->RegisterTag('da_tag_1');
 *       $CACHE_MANAGER->RegisterTag('iblock_id_1');
 *    $CACHE_MANAGER->EndTagCache(); 
 *    $cache->EndDataCache($vars);
 * }
 * ```
 *
 * Как выглядит этот же пример с SimpleCache:
 * ```
 * $cache = new SimpleCache($cacheTime, $cacheId, $cacheDir, ['da_tag_1', 'iblock_id_1']));
 * if ($cache->isValid()) {
 *    $vars = $cache->getVars();
 * } else {
 *    $vars = getMyVars();
 *    $cache->setVars($vars);
 * }
 * ```
 * 
 * Есть ещё более упрощённый способ:
 * ```
 * SimpleCache::get($cacheTime, $cacheId, true, ['da_tag_1', 'iblock_id_1'], fn()=>getMyVars());
 * ```
 *
 * При таком подходе, если данные, выдаваемые getMyVars() в кэше есть - то они и возвращаются,
 * если нету - кэш заполняется свежими данными, после чего они тоже возвращаются.
 *
 * Какой-то вывод таким образом не покэшировать, только код.
 *
 * Дополнительно класс содержит методы очистки кэша,
 * в самом буге позапиханные в разные классы,
 * глобальные переменные, в статическом или нестатическом виде,
 * и частично не документированные.
 *
 * Тут они все статические. Вызываются так:
 * ```
 * SimpleCache::cleanDir($cacheDir);
 * ```
 */
class SimpleCache {
	protected \CPHPCache $phpCache;
	protected string     $cacheDir;
	protected array      $tagsList;

	protected bool $_isValid;



	/**
	 * Инициализация кэша.
	 *
	 * Всё почти как в буге. Только тэги можно сразу передавать.
	 *
	 * @param  int          $cacheLifeTime
	 * @param  string       $cacheId
	 * @param  string|false $cacheDir
	 * @param  array|false  $tagsList
	 * @return bool
	 */
	public function __construct ($cacheLifeTime, $cacheId, $cacheDir = false, $tagsList = false) {
		$this->phpCache = new \CPHPCache();
		$this->cacheDir = $cacheDir;
		$this->tagsList = $tagsList;
		if ($cacheDir === false) {
			$this->_isValid = $this->phpCache->InitCache($cacheLifeTime, $cacheId);
		} else {
			//"Важно, что этот путь начинается со слеша и им не заканчивается." (c)
			if (strpos($this->cacheDir, '/') !== 0) {
				$this->cacheDir = '/'.$this->cacheDir;
			}
			$this->cacheDir = preg_replace('|/+$|', '', $this->cacheDir);
			if (is_null($this->cacheDir)) {
				throw new \Exception("Ошибка обработки cacheDir");
			}
			$this->_isValid = $this->phpCache->InitCache($cacheLifeTime, $cacheId, $this->cacheDir);
		}
	}



	/**
	 * Получение кэшированных данных или заполнение кэша.
	 *
	 * @param  int               $cacheLifeTime
	 * @param  string            $cacheId
	 * @param  string|false|true $cacheDir  Если true - папка получает то же название, что и $cacheId
	 * @param  array|false       $tagsList
	 * @param  mixed             $data      Готовые данные или функция, которая их вернёт
	 * @return mixed
	 */
	public static function get ($cacheLifeTime, $cacheId, $cacheDir, $tagsList, $data) {
		$cache = new static();
		if ($cacheDir === true) {
			$cacheDir = $cacheId;
		}
		if ($cache->initCache($cacheLifeTime, $cacheId, $cacheDir, $tagsList)) {
			$vars = $cache->getVars();
		} else {
			$vars = !is_callable($data) ? $data : call_user_func($data);
			$cache->setVars($vars);
		}
		return $vars;
	}

	
	
	public function isValid (): bool {
		return $this->_isValid;
	}



	public function getVars (): mixed {
		return $this->phpCache->GetVars();
	}



	public function setVars (mixed $vars) {
		global $CACHE_MANAGER;
		$this->phpCache->StartDataCache();
		if (is_array($this->tagsList)) {
			$CACHE_MANAGER->StartTagCache($this->cacheDir);
			foreach ($this->tagsList as $tag) {
				$CACHE_MANAGER->RegisterTag($tag);
			}
			$CACHE_MANAGER->EndTagCache(); 
		}
		$this->phpCache->EndDataCache($vars);
	}



	public function isExpired (string $cacheDir): bool {
		return $this->phpCache->IsCacheExpired($cacheDir);
	}



	public static function clean (string $cacheId) {
		(new \CPHPCache())->Clean($cacheId);
	}

	public static function cleanDir (string $cacheDir) {
		(new \CPHPCache())->CleanDir($cacheDir);
	}

	public static function clearByTag (string $tag) {
		$GLOBALS['CACHE_MANAGER']->ClearByTag($tag);
	}

	public static function clearIblock (int $iblockId) {
		$GLOBALS['CACHE_MANAGER']->ClearByTag("iblock_id_$iblockId");
	}

	public static function clearComponentCache (string $name) {
		\CBitrixComponent::clearComponentCache($name);
	}
}
