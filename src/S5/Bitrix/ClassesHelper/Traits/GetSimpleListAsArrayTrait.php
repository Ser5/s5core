<?
trait GetSimpleListAsArrayTrait {
	/**
	 * @param  array $filter
	 * @return array
	 */
	public function getSimpleListAsArray ($filter) {
		return $this->helper->getSimpleListAsArray(array('sort' => 'asc'), $filter);
	}
}
