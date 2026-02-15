<?
namespace S5\Xtrabackup\Results;



class Result {
	const NONE   = 'none';
	const LOCKED = 'locked';
	const ERROR  = 'error';



	public function __construct (
		public string $code = 'none',
	) {
	}
}
