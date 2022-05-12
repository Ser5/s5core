<?
namespace S5\Web;

class AjaxUtils {
	public static function isAjaxBlockRequested ($blockId) {
		return (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' and
			isset($_REQUEST['ajax_block_id']) and $_REQUEST['ajax_block_id'] == $blockId
		);
	}
}
