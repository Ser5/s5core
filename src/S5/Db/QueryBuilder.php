<?
namespace S5\Db;
use S5\Db\Adapters\IAdapter;


class QueryBuilder {
	protected IAdapter $adapter;
	protected string   $tableName;
	protected string   $idFieldName;
	protected array    $valuesHash;

	/**
	 * Ctor.
	 *
	 * $params:
	 * - adapter
	 * - tableName
	 * - idFieldName
	 * - valuesHash
	 */
	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
	}



	public function getInsert (): string {
		$fieldsString = '';
		$valuesString = '';
		foreach ($this->valuesHash as $field => $value) {
			$fieldsString .= $field.',';
			$valuesString .= "'".$this->adapter->escape($value)."',";
		}
		$fieldsString = substr($fieldsString, 0, -1);
		$valuesString = substr($valuesString, 0, -1);
		$query        = "INSERT INTO {$this->tableName} ($fieldsString) VALUES ($valuesString)";
		return $query;
	}



	public function getUpdate (string $id): string {
		$setString = '';
		foreach ($this->valuesHash as $field => $value) {
			$setString    .= $field.',';
			$valuesString .= "'".$this->adapter->escape($value)."',";
		}
		$setString = substr($setString, 0, -1);
		$query = "UPDATE {$this->tableName} SET $setString WHERE {$this->idFieldName} = '".$this->adapter->escape($id)."'";
		return $query;
	}
}
