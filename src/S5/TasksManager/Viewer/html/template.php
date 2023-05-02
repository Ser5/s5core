<style>[x-cloak] {display:none !important;}</style>

<div class="tasks" x-data="state" x-cloak>
	<div class="tasks__pager">
		<div class="tasks__pager-numbers">
			<template x-for="page in pagesList">
				<template x-if="page.isGap">
					<div class="tasks__pager-item tasks__pager-number tasks__pager-gap">&hellip;</div>
				</template>
				<template x-if="!page.isGap">
					<a :href="page.url" class="tasks__pager-item tasks__pager-number" x-text="page.number"></a>
				</template>
			</template>
		</div>
	</div>
	<table class="tasks__list">
		<tr>
			<th>Задача</th>
			<th>Прогресс</th>
			<th>Логи</th>
		</tr>
		<template x-for="task in tasksList">
			<tr class="tasks__item" :key="task.id">
				<td class="tasks__cell tasks__item-name">
					<div class="tasks__id">№<span x-text="task.id"></span></div>
					<div x-text="task._type_name"></div>
				</td>
				<td class="tasks__cell tasks__item-progress">
					<div class="tasks__progress-bar">
						<div class="tasks__progress-crawler" :style="'width:'+task.progress+'%'"></div>
					</div>
					<div class="tasks__progress-info">
						<template x-if="task.state_id == states.RUNNING">
							<div>
								<div class="tasks__progress-percent">
									<span class="tasks__progress-percent-value" x-text="task.progress"></span>%
								</div>
								<div class="tasks__time-left">
									Осталось:
									<span class="tasks__time-left-value" x-text="task._progress.left_time_data.hms"></span>
								</div>
							</div>
						</template>
						<div :class="'tasks__state tasks__'+task._state_code" x-text="task._state_name"></div>
					</div>
				</td>
				<td class="tasks__cell tasks__item-log">
					<div class="tasks__log">
						<template x-for="log in task._logs_list">
							<div
								:class = "'tasks__log-item tasks__'+log.type"
								:style = "'padding-left:' + ((log.level-1)*20) + 'px;'"
								:key   = "log.id"
								x-html = "log.message"
							></div>
						</template>
					</div>
				</td>
			</tr>
		</template>
	</table>
</div>
