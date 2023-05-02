'strict';
var S5 = S5 ?? {};

S5.TasksViewer = class {
	constructor () {
		const AppRoot = {
			data () {
				return {
					states:           {NEW: 1, RUNNING: 2, DONE: 5},
					params:           {},
					pagesList:        [],
					tasksList:        [],
					tasksAddDataList: [],
					limit:            3,
					pageNumber:       1,
					interval:         undefined,
					visibilityClass:  'hidden',
					//isFirst: true,
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
					//if (this.isFirst) {
						//this.isFirst = false;
						fetch('/ajax.php?' + new URLSearchParams({
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
					//} else {
					//	this.tasksList[0].progress++;
					//}
				},
			},
		};

		const app = Vue.createApp(AppRoot).mount('#app');
	}



	initApp () {
	}
}
