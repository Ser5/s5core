<?
namespace S5\Validator;



class RulesParserTest extends \PHPUnit\Framework\TestCase {
	public function testResults () {
		$validator = new TestValidator();
		$parser    = new RulesParser();

		$list = [
			['pravda()', true],
			['obman()',  false],

			['pravda, ()', true],
			['(), pravda, ()', true],

			['pravda() & pravda()', true],
			['obman()  | obman()',  false],

			['pravda() | obman()',  true],
			['obman()  | pravda()', true],
			['pravda() & obman()',  false],
			['obman()  & pravda()', false],

			['pravda() | obman() & obman()',    true],
			['pravda() & obman() | pravda()',   false],
			['pravda() & (obman() | pravda())', true],

			['obman() & obman() | pravda()',    false],
			['pravda() | obman() & pravda()',   true],
			['(pravda() | obman()) & pravda()', true],

			['pravda() | ((pravda() & pravda()) & pravda())', true],
			['obman()  | ((pravda() & pravda()) & pravda())', true],
			['obman()  | ((obman()  & pravda()) & pravda())', false],
			['obman()  | ((pravda() & obman())  & pravda())', false],
			['obman()  | ((pravda() & pravda()) & obman())',  false],

			['obman() | ((pravda(1) & pravda(2)) & pravda(1, 2))', true],
			['((( obman() | ((pravda(1) & pravda(2)) & pravda(1, 2)) )))', true],

			['obman()|((pravda(1)&pravda(2))&pravda(1,2))', true],
			['obman( ) | ( ( pravda( 1 ) & pravda( 2 ) ) & pravda( 1 , 2 ) )', true],

			['obman|((pravda&pravda)&pravda)', true],
			['obman | ( ( pravda & pravda ) & pravda )', true],
		];

		foreach ($list as $e) {
			$got = $parser->run($validator, 'value', $e[0]);
			$this->assertEquals($e[1], $got);
		}
	}



	public function testExceptions () {
		$validator = new TestValidator();
		$parser    = new RulesParser();

		$ruleStringsList = [
			'pravda ()',
			'() pravda',
			'pravda,, pravda',
			'pravda &| pravda',
			'& pravda',
		];

		$exceptionsAmount = 0;
		foreach ($ruleStringsList as $ruleString) {
			try {
				$parser->run($validator, 'value', $ruleString);
			} catch (RulesException $ex) {
				$exceptionsAmount++;
			}
		}
		$this->assertEquals(count($ruleStringsList), $exceptionsAmount);
	}
}
