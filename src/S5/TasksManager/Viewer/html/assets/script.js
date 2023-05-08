'strict';
var S5 = S5 ?? {};

S5.TasksViewer = class {
	constructor ({ajaxUrl, states, pageNumber = 1, limit = 3}) {
		const AppRoot = {
			data () {
				return {
					ajaxUrl:          ajaxUrl,
					states:           states,
					params:           {},
					pagesList:        [],
					tasksList:        [],
					tasksAddDataList: [],
					limit:            limit,
					pageNumber:       pageNumber,
					interval:         undefined,
					visibilityClass:  'hidden',
				}
			},
			mounted: function () {
				this.update();
				this.setUpdateInterval();
			},
			methods: {
				setUpdateInterval () {
					clearInterval(this.interval);
					this.interval = setInterval(() => this.update(), 1000);
				},
				changePage (pageNumber) {
					this.setUpdateInterval();
					this.pageNumber = pageNumber;
					this.update();
				},
				update () {
					fetch(this.ajaxUrl+'?' + new URLSearchParams({
						limit: this.limit,
						page:  this.pageNumber,
					}))
						.then(r => r.json())
						.then(data => {
							//console.log(data.pagesList[0].isGap);
							this.pagesList       = data.pagesList;
							this.tasksList       = data.tasksList;
							this.visibilityClass = '';
							//this.tasksList[0].progress = 40;
						})
					;
				},
			},
		};

		const app = Vue.createApp(AppRoot).mount('#app');
	}



	initApp () {
	}
}
