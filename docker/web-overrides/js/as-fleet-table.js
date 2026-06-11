// as-fleet-table.js — dependency-free sortable tables for fleet management.
// On load, every table.as-sortable gets clickable headers (except th.nosort).
// Clicking a header sorts the first <tbody>'s rows by that column's cell text,
// numeric-aware, toggling ascending/descending. Whole <tr> nodes are moved so
// form inputs/checkboxes stay intact.
(function () {
	function cellText(row, index) {
		var cell = row.cells[index];
		return cell ? (cell.textContent || cell.innerText || "").trim() : "";
	}

	function isNumeric(value) {
		return value !== "" && !isNaN(parseFloat(value)) && isFinite(value);
	}

	function compare(a, b) {
		if (isNumeric(a) && isNumeric(b)) {
			return parseFloat(a) - parseFloat(b);
		}
		return a < b ? -1 : (a > b ? 1 : 0);
	}

	function sortBy(table, colIndex, ascending) {
		var tbody = table.tBodies[0];
		if (!tbody) return;
		var rows = Array.prototype.slice.call(tbody.rows);
		rows.sort(function (r1, r2) {
			var result = compare(cellText(r1, colIndex), cellText(r2, colIndex));
			return ascending ? result : -result;
		});
		for (var i = 0; i < rows.length; i++) {
			tbody.appendChild(rows[i]);
		}
	}

	function wire(table) {
		var headerRow = table.tHead ? table.tHead.rows[0] : table.rows[0];
		if (!headerRow) return;
		var headers = headerRow.cells;
		for (var c = 0; c < headers.length; c++) {
			var th = headers[c];
			if (th.className && th.className.indexOf("nosort") !== -1) continue;
			(function (th, colIndex) {
				th.style.cursor = "pointer";
				var ascending = true;
				th.addEventListener("click", function () {
					sortBy(table, colIndex, ascending);
					ascending = !ascending;
				});
			})(th, c);
		}
	}

	document.addEventListener("DOMContentLoaded", function () {
		var tables = document.querySelectorAll("table.as-sortable");
		for (var i = 0; i < tables.length; i++) {
			wire(tables[i]);
		}
	});
})();
