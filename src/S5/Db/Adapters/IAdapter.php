<?
namespace S5\Db\Adapters;

interface IAdapter {
	//function withTableNamesPrefix (string $tableNamesPrefix): IAdapter;

	function escape (string $string): string;
	function quote  (string $string): string;

	function query (string $query);


	function fetchObject ($r);

	function fetchAssoc ($r);


	function getObject (string $query);

	function getObjectsList (string $query): array;


	function getAssoc (string $query);

	function getAssocList (string $query): array;


	public function getInsertId (): int;

	function getAffectedRows (): int;
}
