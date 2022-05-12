<?
namespace S5\Web\AntiSpam\Items;
use S5\Web\AntiSpam\CheckResult;

interface IItem {
	function showHtml ();

	function checkForm (): CheckResult;
}
