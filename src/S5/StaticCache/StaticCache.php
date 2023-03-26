<?
namespace S5\StaticCache;
use Closure;



class StaticCache {
	protected string $filePath;

	protected bool     $isDataRead = false;
	protected mixed    $data;
	protected ?Closure $refresher = null;


	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
		if (!$this->filePath) {
			throw new \InvalidArgumentException("filePath не указан");
		}
	}



	public function __get (string $name) {
		if ($name === 'data') {
			if (!$this->isDataRead) {
				if (!is_file($this->filePath)) {
					$this->refresh();
					$this->write();
				} else {
					$this->read();
				}
			}
			return $this->data;
		}
		throw new \Exception("Undefined variable [$name]");
	}



	public function __set (string $name, mixed $data) {
		if ($name === 'data') {
			$this->data = $data;
			$this->write();
			return;
		}
		throw new \Exception("Undefined variable [$name]");
	}



	public function setRefresher (Closure $refresher) {
		$this->refresher = $refresher;
	}



	public function refresh () {
		if (!$this->refresher) {
			throw new \Exception("Refresher isn't set. Use setRefresher() first.");
		}
		$this->data = ($this->refresher)();
		$this->write();
	}



	protected function read () {
		$this->data       = require $this->filePath;
		$this->isDataRead = true;
	}



	protected function write () {
		$file = new \S5\IO\File($this->filePath);
		$file->putContents("<?\nreturn ".var_export($this->data,true).";\n");
		$this->isDataRead = true;
	}
}
