<?
namespace S5\IO\Permissions;



class Permissions {
	public Rwx $owner;
	public Rwx $group;
	public Rwx $others;

	/**
	 * Ctor.
	 *
	 * @param $permissions   Восьмеричное число, типа 0777, 0755 итп.
	 */
	public function __construct (int $permissions) {
		$this->owner  = new Rwx($permissions & 0700);
		$this->group  = new Rwx($permissions & 0070);
		$this->others = new Rwx($permissions & 0007);
	}
}
