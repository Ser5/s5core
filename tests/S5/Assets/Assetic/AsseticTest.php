<?
namespace S5\Assets\Assetic;
use S5\IO\{Directory, File};
use S5\Assets\Assetic\AsseticTestUtils as Utils;


class AsseticTest extends \S5\TestCase {
	private Directory $_documentRoot;
	private File      $_srcMinifierFile;
	private File      $_destMinifierFile;
	private Directory $_asseticJsFilesDir;
	private Directory $_npmDir;


	public function __construct (...$params) {
		parent::__construct(...$params);

		$this->_documentRoot = new Directory(__DIR__.'/files/htdocs/');

		$this->_asseticJsFilesDir = new Directory(__DIR__.'/../../../../src/S5/Assets/Assetic/js/');
		$this->_npmDir            = new Directory(__DIR__.'/files/npm/');

		$this->_srcModulesFile   = new File($this->_asseticJsFilesDir.'/modules.txt');
		$this->_srcMinifierFile  = new File($this->_asseticJsFilesDir.'/minifier.js');
		$this->_destModulesFile  = new File(__DIR__.'/files/npm/modules.txt');
		$this->_destMinifierFile = new File(__DIR__.'/files/npm/minifier.js');
	}



	public function testOriginals () {
		$searchDataHash = [
			'js' => [
				'count'    => '<script',
				'contains' => fn($url)=>'<script src="'.$url.'" defer></script>',
			],
			'css' => [
				'count'    => '<link',
				'contains' => fn($url)=>'<link rel="stylesheet" href="'.$url.'">',
			],
		];

		$expectedUrlsHash = $this->_getExpectedUrlsHash();

		foreach ([Assetic::ORIGINALS, Assetic::ORIGINALS_TS] as $mode) {
			$assetic = Utils::getAssetic(['assetsMode' => $mode]);
			foreach ([Assetic::JS, Assetic::CSS] as $type) {
				$tagsString       = $assetic->getTagsString($type);
				$searchData       = $searchDataHash[$type];
				$expectedUrlsList = $expectedUrlsHash[$type];
				$this->assertEquals(count($expectedUrlsList), substr_count($tagsString, $searchData['count']));
				foreach ($expectedUrlsList as $url) {
					if ($mode == Assetic::ORIGINALS_TS) {
						$url .= '\?t=\d+';
					}
					$this->assertMatchesRegularExpression('~'.$searchData['contains']($url).'~ui', $tagsString);
				}
			}
		}
	}



	public function testConcatenated () {
		$assetic = Utils::getAssetic(['mode' => Assetic::CONCATENATED]);

		$searchDataHash = [
			'js' => [
				'folder'    => 'scripts',
				'code_line' => fn($varName)=>"$varName = '$varName';",
			],
			'css' => [
				'folder'    => 'styles',
				'code_line' => fn($varName)=>".$varName {display:block;}",
			],
		];

		foreach ($searchDataHash as $type => $searchData) {
			ob_start();
			require __DIR__."/files/htdocs/$searchData[folder]/concatenated.php";
			$code = ob_get_clean();

			$matches = [];
			foreach ($this->_getExpectedUrlsHash()[$type] as $url) {
				preg_match('/([\w\d]+)\.\w+$/ui', $url, $matches);
				$codeLine = $searchData['code_line']($matches[1]);
				$this->assertStringContainsString($codeLine, $code);
			}
		}
	}



	public function testMinified () {
		$this->_deleteMinDirsList();
		$this->_prepareNpmDir();

		$assetic = Utils::getAssetic(['mode' => Assetic::MINIFIED]);
		$assetic->generate();

		foreach ([$assetic::JS, $assetic::CSS] as $type) {
			$numbersList = ['001', '002'];
			foreach (AsseticTestUtils::$assetUrlsData[$type] as $fileName => $urlsList) {
				$dirName = ($type == $assetic::JS ? 'scripts' : 'styles');
				$n        = array_shift($numbersList);
				$filePath = "$this->_documentRoot/$dirName/min/{$n}_$fileName.$type";
				$this->assertFileExists($filePath);
			}
		}
	}



	private function _deleteMinDirsList () {
		foreach (['scripts', 'styles'] as $dirName) {
			(new Directory("$this->_documentRoot/$dirName/min/"))->delete();
		}
	}



	private function _prepareNpmDir () {
		if (!is_dir("$this->_npmDir/node_modules/") or !$this->_destModulesFile->isExists() or $this->_srcModulesFile->isMtimeNewer($this->_destModulesFile)) {
			$this->_clearNpmDir();
			$this->_srcModulesFile->copy($this->_destModulesFile);
			passthru("cd \"$this->_npmDir\" && " . $this->_destModulesFile->getContents());
		}
		$this->_srcMinifierFile->copy($this->_destMinifierFile);
	}



	private function _clearNpmDir () {
		$this->_npmDir->clear();
	}



	private function _getExpectedUrlsHash () {
		$expectedUrlsHash = [
			'js'  => [],
			'css' => [],
		];
		foreach (Utils::$assetUrlsData as $type => $typeCollectionsHash) {
			foreach ($typeCollectionsHash as $urlsList) {
				foreach ($urlsList as $url) {
					if (!strpos($url, 'components')) {
						$expectedUrlsHash[$type][] = $url;
					} else {
						$expectedUrlsHash[$type][] = "{$url}comp1.$type";
						$expectedUrlsHash[$type][] = "{$url}comp2.$type";
					}
				}
			}
		}
		return $expectedUrlsHash;
	}
}
