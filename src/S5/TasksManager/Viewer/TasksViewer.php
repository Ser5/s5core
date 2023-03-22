<?
namespace S5\TasksManager\Viewer;
use \S5\TasksManager\TasksManager;



class TasksViewer {
	use \S5\ConstructTrait;

	protected TasksManager $tasksManager;
	protected string       $scriptUrl    = '/assets/script.js';
	protected string       $stylesUrl    = '/assets/styles.css';
	protected string       $templatePath = __DIR__.'/html/template.php';



	public function show (array $params) {
		$tasksManager = $this->tasksManager;

		$tasksList        = $params['tasks_list']          ?? [];
		$tasksAddDataList = $params['tasks_add_data_list'] ?? [];

		$scriptUrl = $this->scriptUrl;
		$stylesUrl = $this->stylesUrl;

		require $this->templatePath;
	}
}
