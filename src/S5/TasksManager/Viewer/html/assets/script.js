'strict';
var S5 = S5 ?? {};

S5.TasksViewer = class {
	constructor ({ajaxUrl, states, updateInterval = 5000}) {
		const AppRoot = {
			data () {
				updateInterval = parseInt(updateInterval);
				if (updateInterval < 1000) updateInterval = 1000;

				let data = {
					ajaxUrl:          ajaxUrl,
					states:           states,
					params:           {},
					pagesList:        [],
					tasksList:        [],
					tasksAddDataList: [],
					itemsPerPage:     this.getItemsPerPageFromCookies(),
					pageNumber:       this.getPageNumberFromUrl(),
					updateInterval:   updateInterval,
					interval:         undefined,
					visibilityClass:  'hidden',
					popStateHandler:  e => this.changePage(e.state?.pageNumber ?? 1),
					itemsPerPageOptionsList: [3, 5, 10, 20, 30, 50, 100],
				}

				if (data.itemsPerPageOptionsList.indexOf(data.itemsPerPage) == -1) {
					data.itemsPerPage = data.itemsPerPageOptionsList[2];
				}

				return data;
			},



			mounted: function () {
				addEventListener('popstate', this.popStateHandler);
				this.update();
				this.setUpdateInterval();
			},



			umounted: function () {
				clearInterval(this.interval);
				removeEventListener('popstate', this.popStateHandler);
			},



			methods: {
				setUpdateInterval () {
					clearInterval(this.interval);
					this.interval = setInterval(() => this.update(), this.updateInterval);
				},
				itemsPerPageChangeHandler (e) {
					document.cookie = `tasks_viewer_items_per_page=${this.itemsPerPage}; max-age=max-age-in-seconds=${86400*365}; samesite=lax`;
					this.setUpdateInterval();
					this.update();
				},
				changePage (pageNumber) {
					this.setUpdateInterval();
					this.pageNumber = pageNumber;
					this.update();
				},
				pageChangeHandler (pageNumber) {
					let url = new URL(window.location);
					url.searchParams.set('page', pageNumber);
					history.pushState({pageNumber}, {}, url.href);
					this.changePage(pageNumber);
				},
				getItemsPerPageFromCookies () {
					return parseInt(document.cookie.match(/tasks_viewer_items_per_page=(\d+)/)?.[1] ?? 10);
				},
				getPageNumberFromUrl () {
					return (new URLSearchParams(location.search).get('page') ?? 1);
				},
				update () {
					fetch(this.ajaxUrl+'?' + new URLSearchParams({
						items_per_page: this.itemsPerPage,
						page:           this.pageNumber,
					}))
						.then(r => r.json())
						.then(data => {
							this.pagesList       = data.pagesList;
							this.tasksList       = data.tasksList;
							this.visibilityClass = '';
						})
					;
				},
			},
		};

		const app = Vue.createApp(AppRoot).mount('#app');
	}
}
