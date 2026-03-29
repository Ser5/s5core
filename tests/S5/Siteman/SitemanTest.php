<?
namespace S5\Siteman;

use S5\Siteman\Templates\Nginx\Basic as NginxBasicTemplate;
use S5\Siteman\Templates\PhpFpm\Main as PhpFpmMainTemplate;

use S5\System;
use S5\IO\{Directory, File};



class SitemanTest extends \S5\TestCase {
	protected Directory $wwwDir;
	protected Directory $etcDir;
	protected Directory $nginxDir;
	protected Directory $phpFpmDir;

	protected Directory $nginxLogsDir;
	protected Directory $phpFpmLogsDir;
	protected Directory $customLogsDir;
	protected File      $indexPhpFile;

	protected Directory $nginxSitesAvailableDir;
	protected Directory $nginxSitesEnabledDir;

	protected Directory $phpFpmPoolsAvailableDir;
	protected Directory $phpFpmPoolsEnabledDir;



	public function testCreateSite () {
		$userGroupString = get_current_user().':'.get_current_user();

		$nginxTemplate  = new NginxBasicTemplate('test', 'test.example.com');
		$phpFpmTemplate = new PhpFpmMainTemplate('test', $userGroupString);

		$sm = new TestSiteman(
			8.4,
			$nginxTemplate,
			$phpFpmTemplate,
			$this->wwwDir,
			$this->nginxSitesAvailableDir,
			$this->nginxSitesEnabledDir,
			$this->phpFpmPoolsAvailableDir,
			$this->phpFpmPoolsEnabledDir,
		);

		$sm->createSite('test', $userGroupString, 'u=rwX,g=rX');

		foreach (['nginxLogsDir', 'phpFpmLogsDir', 'customLogsDir'] as $varName) {
			$this->assertDirectoryExists($this->$varName);
		}
		$this->assertFileExists($this->indexPhpFile);

		foreach (['nginxSitesAvailableDir', 'nginxSitesEnabledDir', 'phpFpmPoolsAvailableDir', 'phpFpmPoolsEnabledDir'] as $varName) {
			$this->assertDirectoryExists($this->$varName);
		}

		$this->assertFileExists("$this->nginxSitesAvailableDir/test");
		$this->assertFileExists("$this->nginxSitesEnabledDir/test");
		$this->assertTrue(is_link("$this->nginxSitesEnabledDir/test"));

		$this->assertFileExists("$this->phpFpmPoolsAvailableDir/test.conf");
		$this->assertFileExists("$this->phpFpmPoolsEnabledDir/test.conf");
		$this->assertTrue(is_link("$this->phpFpmPoolsEnabledDir/test.conf"));

		$this->assertEquals('service php8.4-fpm restart', $sm->commandStringsList[0]);
		$this->assertEquals('nginx -t reload',            $sm->commandStringsList[1]);
	}



	public function setUp (): void {
		parent::setUp();

		$this->filesDir = new Directory(__DIR__.'/files/');

		$this->wwwDir = new Directory("$this->filesDir/www/");
		$this->etcDir = new Directory("$this->filesDir/etc/");

		$this->nginxLogsDir  = new Directory("$this->wwwDir/test/server/logs/nginx/");
		$this->phpFpmLogsDir = new Directory("$this->wwwDir/test/server/logs/php-fpm/");
		$this->customLogsDir = new Directory("$this->wwwDir/test/server/logs/custom/");
		$this->indexPhpFile  = new File(     "$this->wwwDir/test/site/public/index.php");

		$this->nginxDir  = new Directory("$this->etcDir/nginx/");
		$this->phpFpmDir = new Directory("$this->etcDir/php/8.4/fpm/");

		$this->nginxSitesAvailableDir  = new Directory("$this->nginxDir/sites-available/");
		$this->nginxSitesEnabledDir    = new Directory("$this->nginxDir/sites-enabled/");
		$this->phpFpmPoolsAvailableDir = new Directory("$this->phpFpmDir/pool.d-available/");
		$this->phpFpmPoolsEnabledDir   = new Directory("$this->phpFpmDir/pool.d/");

		$this->deleteTestFiles();
	}



	public function tearDown(): void {
		//$this->deleteTestFiles();
		parent::tearDown();
	}
}



class TestSiteman extends Siteman {
	/** @var string[] */
	public array $commandStringsList = [];

	protected function restartPhpFpm () {
		$this->commandStringsList[] = $this->getPhpFpmRestartCommandString();
	}

	protected function restartNginx () {
		$this->commandStringsList[] = $this->getNginxRestartCommandString();
	}
}
