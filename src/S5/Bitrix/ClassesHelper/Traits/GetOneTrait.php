<?
trait GetOneTrait {
	/**
	 * @param  array $filter
	 * @return array
	 */
	public function getOne ($filter) {
		return $this->helper->getOne($filter);
	}
}
