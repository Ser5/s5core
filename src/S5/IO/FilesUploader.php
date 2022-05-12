<?
require_once 'S5/IO/Directory.php';

/**
 * Помещение закачанного пользователем файла в целевую директорию.
 * @package s5core
 */
class S5_IO_FilesUploader {
	/**
	 * Помещает закачанный пользователем файл в целевую директорию.
	 *
	 * Для создаваемых директорий и файлов назначаются полные права доступа.
	 *
	 * @param string $destDir Целевая директория. Если она не существует - будет создана автоматически.
	 * @param string $uploadedFilePath Путь к закачанному файлу.
	 * @param string $fileName Реальное имя закачанного файла.
	 * @param boolean $clearDestDir Очищать ли целевую директорию?
	 */
	public static function UploadFile ($destDir, $uploadedFilePath, $fileName, $clearDestDir = false) {
		//Создание целевой директории.
		if (!file_exists($destDir)) {
			if (!mkdir($destDir, 0777, true)) throw new Exception("Не удалось создать директорию $destDir");
		}
		//Её очистка.
		if ($clearDestDir) {
			S5_IO_Directory::clear($destDir);
		}
		//Перемещение закачанного файла.
		$destFilePath = $destDir.'/'.basename($fileName);
		$move_result = rename($uploadedFilePath, $destFilePath);
		if (!$move_result) throw new Exception("Не удалось переместить файл $uploadedFilePath в директорию $destDir");
		chmod($destFilePath, 0666);
	}
}
