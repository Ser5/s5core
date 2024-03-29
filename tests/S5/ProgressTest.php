<?
namespace S5;

class ProgressTest extends \S5\TestCase {
	public function test () {
		$unitsAmount = 8;

		$expectedData = [
			//Units     Percents   Time
			//e l  t    e    l     e   l   t
			[1, 7, 8,   12,  88,   2,  14, 16],
			[2, 6, 8,   25,  75,   4,  12, 16],
			[3, 5, 8,   37,  63,   6,  10, 16],
			[4, 4, 8,   50,  50,   8,  8,  16],
			[5, 3, 8,   62,  38,   10, 6,  16],
			[6, 2, 8,   75,  25,   12, 4,  16],
			[7, 1, 8,   87,  13,   14, 2,  16],
			[8, 0, 8,   100, 0,    16, 0,  16],
		];

		$time = time();

		$p = new Progress([
			'unitsAmount' => $unitsAmount,
			'timeGetter'  => function () use (&$time) { return $time; }, //Вариант с fn()=>$time тупит
		]);

		$gotData = [];
		for ($a = 1; $a <= $unitsAmount; $a++) {
			$p->add(1);
			$time += 2;
			$gotData[] = [
				$p->getElapsedUnits(),
				$p->getLeftUnits(),
				$p->getTotalUnits(),
				$p->getElapsedPercents(),
				$p->getLeftPercents(),
				$p->getElapsedTime(),
				$p->getLeftTime(),
				$p->getTotalTime(),
			];
		}

		$this->assertEquals($expectedData, $gotData);
	}



	public function testLeftTime () {
		$time = time();

		//Проверяем, что при прогрессе 0 нет деления на ноль,
		//методы получения оставшегося и общего времени не могут определить оставшееся время,
		//поэтому возвращают false.
		//Но getElapsedTime() и getElapsedTimeData() возвращают данные с нулём секунд и прочего.
		$p = new Progress([
			'progress' => 0,
		]);
		$this->assertEquals(0, $p->getElapsedTime());
		$this->assertEquals('0:00:00', $p->getElapsedTimeData()->hms);
		$this->assertFalse($p->getLeftTime());
		$this->assertNull($p->getLeftTimeData());
		$this->assertFalse($p->getTotalTime());
		$this->assertNull($p->getTotalTimeData());

		//За 1 минуту уже выполнен 1%.
		//Ещё осталось выполнить 99% за 99 минут это 1 час, 39 минут, 0 секунд.
		$p = new Progress([
			'startTime'  => $time,
			'progress'    => 1,
			'timeGetter' => fn()=>$time+60,
		]);
		$td = $p->getLeftTimeData();
		$this->assertEquals('1:39:00', $td->hms);
	}
}
