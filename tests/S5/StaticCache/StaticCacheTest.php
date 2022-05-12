<?
namespace S5\StaticCache;
use S5\IO\Directory;



class StaticCacheTest extends \S5\TestCase {
	public function test () {
		$filesDir = new Directory(__DIR__.'/files/');
		$filePath = "$filesDir/cache.php";

		//Изначально данных нет
		$filesDir->delete();

		$sc = new StaticCache(['filePath' => $filePath]);
		$this->assertFileDoesNotExist($filePath);

		//Что если обратиться к данным - которых нет?
		do {
			try {
				$gotData = $sc->data;
			} catch (\Exception $e) {
				break;
			}
			$this->fail("No exception thrown while reading non-existing data");
		} while (false);

		//Установим refresher, чтобы данные могли заполняться
		$expectedData = ['a' => 1, 'b' => 'string'];
		$sc->setRefresher(fn()=>$expectedData);
		$this->assertFileDoesNotExist($filePath);

		//При первом обращении к данным они должны заполниться и записаться в файл
		$gotData = $sc->data;
		//Данные сохраняются сразу и в файл, и внутрь класса. Посмотрим, что файл записался правильный.
		$this->assertFileExists($filePath);
		$this->assertEquals($expectedData, include $filePath);
		//А теперь - что внутри класса
		$this->assertEquals($expectedData, $gotData);

		//Теперь попробуем удалить файл - объект всё ещё должен возвращать уже считанные данные
		$filesDir->delete();
		$this->assertEquals($expectedData, $sc->data);

		//Мы очень даже можем записать данные через присваивание их в $sc->data
		$expectedData = 1;
		$sc->data     = $expectedData;
		$this->assertFileExists($filePath);
		$this->assertEquals($expectedData, include $filePath);
		$this->assertEquals($expectedData, $sc->data);
	}
}
