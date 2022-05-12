<?
namespace S5\IO;

class Factory {
	protected $directoryClass = '\S5\IO\Directory';
	protected $fileClass      = '\S5\IO\File';

	public function __construct (array $params = []) {
		if (isset($params['directory'])) $this->directoryClass = $params['directory_class'];
		if (isset($params['file']))      $this->directoryClass = $params['file_class'];
	}


}
