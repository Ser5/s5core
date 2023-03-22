<?
/**
 * @var array                         $params
 * @var \S5\TasksManager\TasksManager $tasksManager
 * @var array                         $tasksList
 * @var array                         $tasksAddDataList
 * @var string                        $scriptUrl
 * @var string                        $stylesUrl
 */
?>

<?
if ($scriptUrl) echo "<script src='$scriptUrl'></script>";
if ($stylesUrl) echo "<link rel='stylesheet' href='$stylesUrl'>";
?>

<table class="tasks" data-jsc="TasksQueue">
	<?foreach ($tasksList as $task) {?>
		<tr class="tasks__item" data-jsc-part="tasks" data-id="<?=$task->id?>">
			<td class="tasks__item-name">
				<div class="tasks__id">№<?=$task->id?></div>
				<div><?=$task->_type_name?></div>
			</td>
			<td class="tasks__item-progress">
				<div class="tasks__progress-bar">
					<div class="tasks__progress-crawler" style="width:<?=$task->progress?>%" data-jsc-part="crawler"></div>
				</div>
				<div class="tasks__progress-info">
					<?if ($task->state_id == $tasksManager::RUNNING) {?>
						<div class="tasks__progress-percent">
							<span class="tasks__progress-percent-value" data-jsc-part="percent"><?=$task->progress?></span>%
						</div>
						<div class="tasks__time-left">
							Осталось:
							<span class="tasks__time-left-value" data-jsc-part="timeLeft"><?=join(':', $task->_progress->getLeftTimeData()->hms)?></span>
						</div>
					<?}?>
					<div class="tasks__state tasks_<?=$task->_state_code?>"><?=$task->_state_name?></div>
				</div>
			</td>
			<td class="tasks__item-log">
				<div class="tasks__log" data-jsc-part="log">
					<template>
						<div class="tasks__log-item" data-jsc-part="logTemplate"></div>
					</template>
					<?foreach ($task->_logs as $e) {?>
						<div class="tasks__log-item tasks__<?=$e->type?>" style="padding-left:<?=($e->level-1)*20?>px;"><?=$e->message?></div>
					<?}?>
				</div>
			</td>
		</tr>
	<?}?>
</table>
