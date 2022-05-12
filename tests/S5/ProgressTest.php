<?
namespace S5;

class ProgressTest extends \S5\TestCase {
	public function test () {
		$time = time();
		Progress::setTimeGetter(function () use (&$time) {
			return $time;
		});

		$unitsAmount = 8;

		$expectedData = [
			[1, 7, 8,   12,  88,   2,  14, 16],
			[2, 6, 8,   25,  75,   4,  12, 16],
			[3, 5, 8,   37,  63,   6,  10, 16],
			[4, 4, 8,   50,  50,   8,  8,  16],
			[5, 3, 8,   62,  38,   10, 6,  16],
			[6, 2, 8,   75,  25,   12, 4,  16],
			[7, 1, 8,   87,  13,   14, 2,  16],
			[8, 0, 8,   100, 0,    16, 0,  16],
		];

		$p       = new Progress($unitsAmount);
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
}
