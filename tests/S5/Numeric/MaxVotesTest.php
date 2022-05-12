<?php

require_once 'S5/Numeric/MaxVotes.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * S5_Numeric_MaxVotes test case.
 */
class S5_Numeric_MaxVotesTest extends \PHPUnit\Framework\TestCase {
	public function test1 () {
		$votes = array(100, 50, 25, 25);
		$mv = new S5_Numeric_MaxVotes($votes);
		$results_max = $mv->getResultsFromMax();
		$results_sum = $mv->getResultsFromSum();
		$expected_max = array(100, 50, 25, 25);
		$expected_sum = array(50, 25, 12.5, 12.5);
		for ($a=0; $a<count($votes); $a++) {
			$this->assertEquals($expected_max[$a], $results_max[$a]);
			$this->assertEquals($expected_sum[$a], $results_sum[$a]);
		}
	}
}

