<?
namespace S5\BatchDb;

class BatchesFactory {
	protected array $params;

	/**
	 * @param array $params Общие предварительные настройки для всех getInsert() и getUpdate()
	 */
	public function __construct (array $params) {
		$this->params = $params;
	}



	/**
	 * @param array $params Настройки для конкретного BatchInsert, переопределяют настройки из конструктора.
	 */
	public function getInsert (array $params = []): BatchInsert {
		return new BatchInsert($this->params + $params);
	}

	/**
	 * @param array $params Настройки для конкретного BatchInsert, переопределяют настройки из конструктора.
	 */
	public function getUpdate (array $params = []): BatchUpdate {
		return new BatchUpdate($this->params + $params);
	}
}
