<?
namespace S5\Web\AntiSpam\Items;

interface IItem {
	function showHtml ();

	function checkForm (): \S5\Web\AntiSpam\CheckResult;
}
