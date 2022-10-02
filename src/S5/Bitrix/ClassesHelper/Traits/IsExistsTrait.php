<?
trait IsExistsTrait {
	/**
	 * @param  array $filter
	 * @return array
	 */
	public function isExists ($filter) {
		return $this->helper->isExists($filter);
	}
}
