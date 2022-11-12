<?
namespace S5;

class ProgressTimeData {
	public int    $total;
	public int    $h;
	public int    $m;
	public int    $s;
	public string $hms;

	public function __construct (int $leftTime) {
		$this->total = $leftTime;
		$this->h     = floor($leftTime / 3600);
		$leftTime   -= ($this->h * 3600);
		$this->m     = floor($leftTime / 60);
		$leftTime   -= ($this->m * 60);
		$this->s     = $leftTime;
		$this->hms =
			$this->h .':'.
			str_pad($this->m, 2, STR_PAD_LEFT) .':'.
			str_pad($this->s, 2, STR_PAD_LEFT)
		;
	}
}
