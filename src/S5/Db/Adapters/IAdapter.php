<?
namespace S5\Adapters;

interface IAdapter {
	function escape (string $query): string;

	function query (string $query);

	function getAssoc (string $query): array;

	function getAffectedRows (): int;
}
