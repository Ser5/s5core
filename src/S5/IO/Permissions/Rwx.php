<?
namespace S5\IO\Permissions;



class Rwx {
	public bool $r;
	public bool $w;
	public bool $x;

	public function __construct (int $flags) {
		$this->r = (bool)($flags & 04);
		$this->w = (bool)($flags & 02);
		$this->x = (bool)($flags & 01);
	}
}
