var S5 = S5 ?? {};

S5.TasksViewer = class {
	constructor () {
		this._state = {};
		this.initState();
		document.addEventListener('alpine:init', () => {
			let state = this.getState();
			Alpine.data('state', ()=>state);
		});
	}



	getState () {
		return this._state;
	}



	initState () {
		this._state = {
			states:           {NEW: 1, RUNNING: 2, DONE: 5},
			params:           {},
			pagesList:        [],
			tasksList:        [],
			tasksAddDataList: [],
			interval:         undefined,
			init: function () {
				this.update();
				this.interval = setInterval(() => this.update(), 1000);
			},
			update: function () {
				fetch('/ajax.php?' + new URLSearchParams({
					limit: 3,
					page:  2,
				}))
					.then(r => r.json())
					.then(data => {
						console.log(data.pagesList[0].isGap);
						this.pagesList = data.pagesList;
						this.tasksList = data.tasksList;
						/*if (data.progress == 100) {
							clearInterval(this.interval);
						}*/
					})
				;
			},
		};
	}
}
