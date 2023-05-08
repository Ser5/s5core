<style>.hidden {display:none;}</style>

<div id="app">
<div :class="'tasks ' + visibilityClass">
	<div class="tasks__pager">
		<div class="tasks__pager-numbers">
			<template v-for="page in pagesList">
				<div class="tasks__pager-item tasks__pager-number tasks__pager-gap" v-if="page.isGap">&hellip;</div>
				<a :href="page.url" class="tasks__pager-item tasks__pager-number" v-if="!page.isGap" v-text="page.number" @click.prevent="changePage(page.number)"></a>
			</template>
		</div>
	</div>
	<table class="tasks__list">
		<tr>
			<th>Задача</th>
			<th>Прогресс</th>
			<th>Логи</th>
		</tr>
		<template v-for="task in tasksList">
			<tr class="tasks__item" :key="task.id">
				<td class="tasks__cell tasks__item-name">
					<div class="tasks__id">№<span v-text="task.id"></span></div>
					<div v-text="task._type_name"></div>
				</td>
				<td class="tasks__cell tasks__item-progress">
					<div class="tasks__progress-bar">
						<div class="tasks__progress-crawler" :style="'width:'+task.progress+'%'"></div>
					</div>
					<div class="tasks__progress-info">
						<template v-if="task.state_id == states.RUNNING">
							<div>
								<div class="tasks__progress-percent">
									<span class="tasks__progress-percent-value" v-text="task.progress"></span>%
								</div>
								<div class="tasks__time-left">
									Осталось:
									<span class="tasks__time-left-value" v-text="task._progress.left_time_data.hms"></span>
								</div>
							</div>
						</template>
						<div :class="'tasks__state tasks__'+task._state_code" v-text="task._state_name"></div>
					</div>
				</td>
				<td class="tasks__cell tasks__item-log">
					<div class="tasks__log">
						<div
							v-for="log in task._logs" :key="log.id"
							:class = "'tasks__log-item tasks__'+log.type"
							:style = "'padding-left:' + ((log.level-1)*20) + 'px;'"
							v-html = "log.message"
						></div>
					</div>
				</td>
			</tr>
		</template>
	</table>
</div>
</div>
