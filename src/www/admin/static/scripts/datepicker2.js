(function () {
	DATEPICKER_L10N = {};
	DATEPICKER_L10N.en = {
		weekdays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
	};
	DATEPICKER_L10N.fr = {
		weekdays: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
		months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
		labels: {
			"Previous month": "Mois précédent",
			"Next month": "Mois suivant",
			"Change year": "Choisir l'année",
			"Change month": "Choisir le mois",
		}
	};

	window.DatePicker = class {
		constructor(button, input, config) {
			this.button = button;
			this.input = input;
			this.date = null;
			this.nav = [];
			this.header = null;

			Object.assign(this, {
				format: 0, // 0 = Y-m-d, 1 = d/m/Y
				lang: 'fr',
				class: 'datepicker'
			}, config);

			var c = document.createElement('dialog');
			c.className = this.class;
			this.container = button.parentNode.insertBefore(c, button.nextSibling);

			button.addEventListener('click', () => this.container.hasAttribute('open') ? this.close() : this.open(), false);
		}

		open()
		{
			var d = '';

			if (this.input) {
				d = this.input.value;
			}
			else if (this.button.dataset && this.button.dataset.date) {
				d = this.button.dataset.date;
			}

			if (d == '') {
				d = new CalendarDate;
			}
			else if (this.format == 1) {
				d = d.split('/');
				d = new CalendarDate(d[2], d[1] - 1, d[0]);
			}
			else {
				d = new CalendarDate(d);
			}

			if (isNaN(d.getTime())) {
				d = new CalendarDate;
			}

			this.date = d;

			this.buildHeader();
			this.refresh();

			this.focus();

			this.container.setAttribute('open', 'open');

			this.keyEvent = (e) => {
				// Ignore modifiers
				if (e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) {
					return true;
				}

				if (e.key == 'Escape') {
					return !!this.close();
				}

				if (this.header.contains(e.target)) {
					return true;
				}


				var r = this.key(e.key);

				if (!r) {
					e.preventDefault();
				}

				return r;
			};

			this.clickEvent = (e) => {
				if (this.container.contains(e.target)) {
					return true;
				}

				if (this.button.contains(e.target)) {
					return true;
				}

				this.close();
			};

			document.addEventListener('keydown', this.keyEvent, true);
			document.addEventListener('click', this.clickEvent, true);
		}

		key(key)
		{
			switch (key) {
				case 'Enter': return !!this.select();
				case 'ArrowLeft': return !!this.day(-1);
				case 'ArrowRight': return !!this.day(1);
				case 'ArrowUp': return !!this.day(-7);
				case 'ArrowDown': return !!this.day(7);
				case 'PageDown': return !!this.month(1, true);
				case 'PageUp': return !!this.month(-1, true);
			}

			return true;
		}

		close()
		{
			this.container.innerHTML = '';
			this.container.removeAttribute('open');
			this.input ? this.input.select() : null;

			document.removeEventListener('keydown', this.keyEvent, true);
			document.removeEventListener('click', this.clickEvent, true);
		}

		generateTable()
		{
			var c = (e) => { return document.createElement(e); }
			var table = c('table'),
				head = c('thead'),
				headRow = c('tr'),
				body = c('tbody');

			DATEPICKER_L10N[this.lang].weekdays.forEach((d) => {
				var cell = c('td');
				cell.innerHTML = d;
				headRow.appendChild(cell);
			});

			head.appendChild(headRow);
			table.appendChild(head);

			var weeks = this.date.getCalendarArray();

			weeks.forEach((week) => {
				var row = c('tr');

				week.forEach((day) => {
					var cell = c('td');

					if (day) {
						cell.innerHTML = `<input type="button" value="${day.getDate()}" />`;
						cell.firstChild.onclick = (e) => this.select(e.target.value);
						cell.firstChild.onfocus = (e) => this.date.setDate(e.target.value) && this.focus(false);
					}

					row.appendChild(cell);
				});

				body.appendChild(row);
			});

			table.appendChild(body);

			return table;
		}

		buildHeader()
		{
			let labels = ["Previous month", "Next month", "Change month", "Change year"];
			labels = labels.map((l) => DATEPICKER_L10N[this.lang].labels[l] || l);

			let j = 0;
			let options = DATEPICKER_L10N[this.lang].months.map((m) => `<option value="${j++}">${m}</option>`);
			let year = this.date.getFullYear();

			this.header = document.createElement('nav');
			this.header.innerHTML = `<input type="button" value="←" title="${labels[0]}" />
				<span><select title="${labels[2]}">${options}</select> <input type="number" size="4" step="1" min="1" max="2500" title="${labels[3]}" value="${year}"></span>
				<input type="button" value="→" title="${labels[1]}" />`;

			this.nav = this.header.querySelectorAll('input, select');
			this.nav[0].onclick = () => { this.month(-1, true); return false; };
			this.nav[3].onclick = () => { this.month(1, true); return false; };
			this.nav[1].value = this.date.getMonth();
			this.nav[1].onchange = () => this.month(this.nav[1].value, false);
			this.nav[2].onchange = () => this.year(this.nav[2].value);
			this.nav[2].onclick = (e) => e.target.select();
			this.nav[2].onkeyup = () => { let y = this.nav[2].value; y.length == 4 ? this.year(y) : null; };

			this.container.appendChild(this.header);
		}

		refresh()
		{
			this.nav[1].value = this.date.getMonth();
			this.nav[2].value = this.date.getFullYear();
			let c = this.container.childNodes;
			c.length == 2 ? c[1].remove() : null;
			this.container.appendChild(this.generateTable());
		}

		year(y)
		{
			if (this.date.getFullYear() == y) {
				return;
			}

			this.date.setYear(y);
			this.refresh();
		}

		month(change, relative)
		{
			let m = relative ? this.date.getMonth() + change : change;
			this.date.setMonth(m);

			// When the date of the day is > number of days in month, it switches to following month
			// so we change the current date as well
			if (this.date.getMonth() != m) {
				this.date.setDate(0);
				this.date.setMonth(m);
			}

			this.refresh();
			this.focus(false);
		}

		day(change)
		{
			var old = new CalendarDate(this.date);
			this.date.setDate(this.date.getDate() + change);

			if (this.date.getMonth() != old.getMonth()) {
				this.refresh();
			}

			this.focus();
		}

		select(s)
		{
			if (s) {
				this.date.setDate(parseInt(s, 10));
			}

			var y = this.date.getFullYear(),
				m = ('0' + (this.date.getMonth() + 1)).substr(-2),
				d = ('0' + this.date.getDate()).substr(-2);

			let v = this.format == 1 ? d + '/' + m + '/' + y : y + '-' + m + '-' + d;

			if (this.input) {
				this.input.value = v;
			}

			if (this.button.dataset && this.button.dataset.date) {
				this.button.dataset.date = v;
			}

			this.close();

			var event = document.createEvent('HTMLEvents');
			event.initEvent('change', true, true);
			event.eventName = 'change';
			(this.input || this.button).dispatchEvent(event);
		}

		focus(nofocus)
		{
			this.container.querySelectorAll('tbody td').forEach((cell) => {
				if (!cell.firstChild) {
					return;
				}

				var v = parseInt(cell.firstChild.value, 10);

				if (v === this.date.getDate()) {
					if (nofocus !== false) {
						cell.firstChild.focus();
					}
					cell.className = 'focus';
				}
				else {
					cell.className = '';
				}
			});
		}
	}

	class CalendarDate extends Date {
		getCalendarArray() {
			var date = new CalendarDate(this.getFullYear(), this.getMonth(), 1);
			var days = [];

			var day = date.getDayOfWeek();

			for (var i = 0; i < day - 1; i++) {
				days.push(null);
			}

			while (date.getMonth() === this.getMonth()) {
				days.push(new CalendarDate(date));
				date.setDate(date.getDate() + 1);
			}

			day = date.getDayOfWeek();
			for (var i = 0; i <= 7 - day; i++) {
				days.push(null);
			}

			var weeks = [];
			while (days.length) {
				weeks.push(days.splice(0, 7));
			}

			return weeks;
		}

		getDayOfWeek() {
			var day = this.getDay();
			if (day == 0) return 7;
			return day;
		}
	}

}());