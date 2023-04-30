<?
namespace S5\Pager;

class Page {
	const NUMBER = 'number';
	const FIRST  = 'first';
	const REW    = 'rew';
	const PREV   = 'prev';
	const NEXT   = 'next';
	const FF     = 'ff';
	const LAST   = 'last';
	const GAP    = 'gap';

	protected string $type;
	/** @var int|false */
	protected $number = false;
	/** @var string|false */
	protected $url = false;
	protected bool $isClickable = false;



	public function __construct (string $type, $number = false, $linker = false, bool $isClickable = false) {
		$this->type = $type;
		if ($number !== false) {
			$this->number = $number;
			if ($linker !== false) {
				$this->url = $linker($number);
			}
		}
		$this->isClickable = $isClickable;
	}



	public function getType (): string { return $this->type; }

	public function getNumber (): int { return $this->number; }

	public function getUrl (): string { return $this->url; }



	public function isClickable (): bool { return $this->isClickable; }

	public function isButton (): bool {
		$buttonCodesHash = [static::FIRST => true, static::REW => true, static::PREV => true, static::NEXT => true, static::FF => true, static::LAST => true];
		return isset($buttonCodesHash[$this->type]);
	}

	public function isSequence (): bool { return ($this->type == static::NUMBER or $this->type == static::GAP); }

	public function isFirst ():    bool { return $this->type == static::FIRST; }

	public function isRew ():      bool { return $this->type == static::REW; }

	public function isPrev ():     bool { return $this->type == static::PREV; }

	public function isNext ():     bool { return $this->type == static::NEXT; }

	public function isFF ():       bool { return $this->type == static::FF; }

	public function isLast ():     bool { return $this->type == static::LAST; }

	public function isGap ():      bool { return $this->type == static::GAP; }

	public function isNumber ():   bool { return $this->type == static::NUMBER; }



	public function __toString (): string {
		return $this->url;
	}
}
