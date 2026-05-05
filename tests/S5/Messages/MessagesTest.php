<?
namespace S5\Messages;

use S5\Messages\Ru\Notes;
//use S5\Messages\Ru\Errors;



class MessagesTest extends \S5\TestCase {
	public function testMessages () {
		$notes  = new Notes();
		//$errors = new Errors();

		$m = $notes->get('required');
		$this->assertEquals('обязательное', $m);

		$m = $notes->get('length_between', [5, 255]);
		$this->assertEquals('длина 5-255 символов', $m);

		$m = $notes->get('object');
		$this->assertEquals('объект', $m);

		$m = $notes->get('object_of', ['Test']);
		$this->assertEquals('объект Test', $m);
	}
}
