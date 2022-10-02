<?
trait BaseTrait {
	protected ClassesHelper $helper;

	public function initClassesHelper ($parent) {
		$this->helper = new ClassesHelper([
			'object' => $this,
			'parent' => $parent,
		]);
	}
}
