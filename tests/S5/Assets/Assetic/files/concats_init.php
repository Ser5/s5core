<?
require_once __DIR__.'/../../../../phpunit_bootstrap.php';

use S5\Assets\Assetic\{Assetic, AsseticTestUtils};

$assetic = AsseticTestUtils::getAssetic(['assetsMode' => Assetic::CONCATENATED]);
