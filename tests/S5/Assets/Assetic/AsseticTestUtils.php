<?
namespace S5\Assets\Assetic;

class AsseticTestUtils {
	public static $assetUrlsData = [
		'js'  => [
			'external_js' => [
				"/scripts/libs/lib1.js",
				"/scripts/libs/lib2.js",
			],
			'local_js' => [
				"/scripts/components/",
				"/scripts/script.js",
			],
		],
		'css' => [
			'external_css' => [
				"/styles/libs/lib1.css",
				"/styles/libs/lib2.css",
			],
			'local_css' => [
				"/styles/components/",
				"/styles/styles.css",
			],
		],
	];

	public static function getAssetic (array $params = []): Assetic {
		$f = __DIR__.'/files/';

		$defaultParams = [
			'assetsMode'         => Assetic::ORIGINALS,
			'dbFilePath'         => "$f/db.php",
			'documentRootPath'   => "$f/htdocs/",
			'npmPath'            => "$f/npm/",
			'assetUrlsData'      => static::$assetUrlsData,
			'jsMinDirUrl'        => "/scripts/min/",
			'cssMinDirUrl'       => "/styles/min/",
			'jsConcatenatorUrl'  => "/scripts/concatenated.php",
			'cssConcatenatorUrl' => "/styles/concatenated.php",
			'jsTagsTemplate'     => '<script src="$url" defer></script>',
			'cssTagsTemplate'    => '<link rel="stylesheet" href="$url">',
		];

		$assetic = new Assetic(array_replace_recursive($defaultParams, $params));

		return $assetic;
	}
}
