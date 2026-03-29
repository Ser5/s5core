<?
namespace S5\Siteman;

use S5\Siteman\Templates\ITemplate as ITemplate;

use S5\System;
use S5\IO\{Directory, File};



class Siteman {
	protected Directory $wwwDir;
	protected Directory $availableSitesDir;
	protected Directory $enabledSitesDir;
	protected Directory $availablePoolsDir;
	protected Directory $enabledPoolsDir;



	public function __construct (
		protected float     $phpVersion,
		protected ITemplate $nginxTemplate,
		protected ITemplate $phpFpmTemplate,
		string              $wwwDirPath            = '',
		string              $availableSitesDirPath = '',
		string              $enabledSitesDirPath   = '',
		string              $availablePoolsDirPath = '',
		string              $enabledPoolsDirPath   = '',
	) {
		$wwwDirPath            = $wwwDirPath            ?: "/var/www/";
		$availableSitesDirPath = $availableSitesDirPath ?: "/etc/nginx/sites-available/";
		$enabledSitesDirPath   = $enabledSitesDirPath   ?: "/etc/nginx/sites-available/";
		$availablePoolsDirPath = $availablePoolsDirPath ?: "/etc/php/pool.d-available/";
		$enabledPoolsDirPath   = $enabledPoolsDirPath   ?: "/etc/php/$phpVersion/fpm/pool.d/";

		$this->wwwDir            = new Directory($wwwDirPath);
		$this->availableSitesDir = new Directory($availableSitesDirPath);
		$this->enabledSitesDir   = new Directory($enabledSitesDirPath);
		$this->availablePoolsDir = new Directory($availablePoolsDirPath);
		$this->enabledPoolsDir   = new Directory($enabledPoolsDirPath);
	}



	public function createSite (string $name, string $chown = 'www-data:www-data', string|int $chmod = '') {
		$availableSiteFile = new File("$this->availableSitesDir/$name");
		$enabledSiteFile   = new File("$this->enabledSitesDir/$name");

		$availablePoolFile = new File("$this->availablePoolsDir/$name.conf");
		$enabledPoolFile   = new File("$this->enabledPoolsDir/$name.conf");

		if ($availableSiteFile->isExists()) {
			throw new \InvalidArgumentException("Конфиг сайта для nginx уже существует: $availableSiteFile");
		}
		if ($availablePoolFile->isExists()) {
			throw new \InvalidArgumentException("Пул сайта для php-fpm уже существует: $availablePoolFile");
		}

		//   /var/www/
		$rootDir       = new Directory("$this->wwwDir/$name/");
		$siteDir       = new Directory("$rootDir/site/");
		$serverDir     = new Directory("$rootDir/server/");
		$publicDir     = new Directory("$siteDir/public/");
		$nginxLogsDir  = new Directory("$serverDir/logs/nginx/");
		$phpFpmLogsDir = new Directory("$serverDir/logs/php-fpm/");
		$customLogsDir = new Directory("$serverDir/logs/custom/");
		foreach (['serverDir', 'publicDir', 'nginxLogsDir', 'phpFpmLogsDir', 'customLogsDir'] as $varName) {
			$$varName->create();
		}

		//Тестовый файл
		file_put_contents("$publicDir/index.php", "<?phpinfo();\n");

		//Владелец, права
		if ($chown) {
			[$user, $group] = explode(':', $chown);
			$rootDir->chown($user, $group, true);
		}
		if ($chmod) {
			$rootDir->chmod($chmod, true);
		}

		//nginx
		$siteConfigText = $this->nginxTemplate->getText();
		$availableSiteFile->putContents($siteConfigText);
		$availableSiteFile->symlink($enabledSiteFile);

		//php-fpm
		$poolText = $this->phpFpmTemplate->getText();
		$availablePoolFile->putContents($poolText);
		$availablePoolFile->symlink($enabledPoolFile);

		//Перезапуск
		$this->restartPhpFpm();
		$this->restartNginx();
	}



	protected function getPhpFpmRestartCommandString (): string {
		return "service php{$this->phpVersion}-fpm restart";
	}

	protected function getNginxRestartCommandString (): string {
		return "nginx -t reload";
	}

	protected function restartPhpFpm () {
		System::exec($this->getPhpFpmRestartCommandString());
	}

	protected function restartNginx () {
		System::exec($this->getNginxRestartCommandString());
	}
}
