<?
namespace S5\Adapters;

class MysqlAdapter implements IAdapter {
	public function escape (string $query): string {
		return mysql_real_escape_string($query);
	}



	public function query (string $query) {
		mysql_query($query);
	}



	public function getAssoc (string $query): array {
		return mysql_fetch_assoc(mysql_query($query));
	}



	public function getAffectedRows (): int {
		return mysql_affected_rows();
	}
}
