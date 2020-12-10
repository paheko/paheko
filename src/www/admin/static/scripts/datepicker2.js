(function () {
	DATEPICKER_L10N = {};
	DATEPICKER_L10N.en = {
		weekdays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
	};
	DATEPICKER_L10N.fr = {
		weekdays: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
		months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
	};

	window.DatePicker = class {
		constructor(button, input, config) {
			this.button = button;
			this.input = input;
			this.date = null;

			Object.assign(this, {
				format: 0, // 0 = Y-m-d, 1 = d/m/Y
				lang: 'fr',
				class: 'datepicker',
				onchange: null
			}, config);

			var c = document.createElement('dialog');
			c.className = this.class;
			this.container = button.parentNode.insertBefore(c, button.nextSibling);

			button.onclick = () => { this.container.hasAttribute('open') ? this.close() : this.open() };
		}

		open()
		{
			var d = this.input ? this.input.value : '';

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

			this.date = d;

			this.refresh();

			this.focus();

			this.container.setAttribute('open', 'open');

			this.keyEvent = (e) => {
				var r = this.key(e.key);

				if (!r) {
					e.preventDefault();
				}

				return r;
			};

			document.addEventListener('keydown', this.keyEvent);
		}

		key(key)
		{
			switch (key) {
				case 'Enter': return !!this.select();
				case 'Escape': return !!this.close();
				case 'ArrowLeft': return !!this.day(-1);
				case 'ArrowRight': return !!this.day(1);
				case 'ArrowUp': return !!this.day(-7);
				case 'ArrowDown': return !!this.day(7);
				case 'PageDown': return !!this.month(1);
				case 'PageUp': return !!this.month(-1);
			}

			return true;
		}

		close()
		{
			this.container.innerHTML = '';
			this.container.removeAttribute('open');

			document.removeEventListener('keydown', this.keyEvent);
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
					cell.innerHTML = day ? day.getDate() : '';
					cell.onclick = (e) => { this.select(e); };
					row.appendChild(cell);
				});

				body.appendChild(row);
			});

			table.appendChild(body);

			return table;
		}

		refresh()
		{
			this.container.innerHTML = '';
			var header = document.createElement('nav');

			var p = document.createElement('input');
			p.type = 'button';
			p.value = '←';
			p.onclick = () => { this.month(-1); return false; };
			header.appendChild(p);

			var t = document.createElement('h3');
			t.innerHTML = DATEPICKER_L10N[this.lang].months[this.date.getMonth()] + ' ' + this.date.getFullYear();
			header.appendChild(t);

			var n = p.cloneNode(true);
			n.value = '→';
			n.onclick = () => { this.month(1); return false; };
			header.appendChild(n);

			this.container.appendChild(header);
			this.container.appendChild(this.generateTable());
		}

		month(change)
		{
			this.date.setMonth(this.date.getMonth() + change);
			this.refresh();
			this.focus();
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

		select(e)
		{
			if (e && e.target.textContent.match(/\d+/)) {
				this.date.setDate(parseInt(e.target.textContent, 10));
			}

			var y = this.date.getFullYear(),
				m = ('0' + (this.date.getMonth() + 1)).substr(-2),
				d = ('0' + this.date.getDate()).substr(-2);

			let v = this.format == 1 ? d + '/' + m + '/' + y : y + '-' + m + '-' + d;

			if (this.input) {
				this.input.value = v;
			}

			this.close();

			if (this.onchange) {
				this.onchange(v, this);
			}
		}

		focus()
		{
			this.container.querySelectorAll('tbody td').forEach((cell) => {
				var v = parseInt(cell.innerHTML, 10);

				if (v === this.date.getDate()) {
					cell.className = 'focus';
				}
				else {
					cell.className = '';
				}
			});

			this.container.focus();
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