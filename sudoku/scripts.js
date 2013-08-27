
function Sudoku(box, data)
{
	this.box = $(box);
//	this.cells = {};
	this.cells = [];
	this.showCandidates = true;
	this.constructMode = false;
	// режим, при котором при клике на ячейку будет выбран исключаемый вариант
	this.excludeMode = false;

	this.cols = {};
	this.rows = {};
	this.blocks = {};

	var i, j, tr, td;
	for (i = 1; i <= 9; i++) {
//		this.cells[i] = {};
		tr = $('<tr>').appendTo(this.box);
		for (j = 1; j <= 9; j++) {
			td = $('<td><div class="td"></div></td>').appendTo(tr).find('div');
//			this.cells[i][j] = new SudokuCell(this, i, j, td);
			this.cells.push(new SudokuCell(this, i, j, td));
		}
	}

	this.each(function(cell){ cell.findSiblings(); });
	this.recalc();
}
Sudoku.prototype = {
	each: function(callback)
	{
		if (!$.isFunction(callback)) return;
		for (var i = 0, len = this.cells.length; i < len; i++)
			callback(this.cells[i]);
	},
	setConstructMode: function(isEnabled)
	{
		this.constructMode = isEnabled;
	},
	toggleCandidates: function()
	{
		this.showCandidates = !this.showCandidates;
		this.each(function(cell){ cell.updateView(); })
	},
	enableExcludeMode: function()
	{
		this.excludeMode = true;
		$('#btn-exclude').addClass('enabled');
	},
	disableExcludeMode: function()
	{
		this.excludeMode = false;
		$('#btn-exclude').removeClass('enabled');
	},
	recalc: function()
	{
		this.each(function(cell){ cell.update(); })
	},
	save: function()
	{
		var data = [];
		this.each(function(cell){ data.push(cell.exportData()); });
		$.post('index.php?ajax=save', {data: data}, function(response){
//			if (response != 'ok')
				alert(response);
		});
	},
	load: function()
	{
		var self = this;
		$.get('data.txt', function(response){
			if (response) {
				for (i = 0; i < response.length; i++)
					self.cells[i].importData(response[i]);
				self.recalc();
			}
		}, 'json');
	},
	searchUnique: function()
	{
		var setTypes = ['cols', 'rows', 'blocks'];
		for (var i in setTypes) {
			if (this._searchUnique(setTypes[i]))
				return;
		}
	},
	_searchUnique: function(setType)
	{
		var candidates = {};
		var found = false;
		var i, j, k, set, val, num, lastCell;

		log('search in ' + setType + '');
		for (i = 1; i <= 9; i++) { // перебор по всем блокам

			for (j = 1; j <= 9; j++)
				candidates[j] = true;

			set = this[setType][i];
//			log('<b>block ' + i + '</b>');

			// поиск уже введенных цифр
			for (j = 0; j < set.length; j++) {
				if (val = set[j].val()) {
					candidates[val] = false;
				}
			}

			// поиск цифр которые встречаются в блоке лишь один раз
			for (j = 1; j <= 9; j++) { // перебор рассматриваемых цифр
				if (!candidates[j]) continue;
				num = 0;
				lastCell = null;
				for (k = 0; k < set.length; k++) { // перебор ячеек в блоке
					if (!set[k].val() && $.inArray(j, set[k].candidates) > -1) {
						num++;
						lastCell = set[k];
					}
				}
//				log('digit ' + j + ': ' + num + ' variants');
				if (num == 1) {
					found = true;
					alert(setType.substr(0, setType.length - 1) + ': ' + i + ', unuque: ' + j);
					return true;
				}
			}
//			alert('block end');
		}

		if (!found) {
			log('nothing found');
			return false;
		}
	}
};


function SudokuCell(boardClass, row, col, td)
{
	var self = this;

	this.boardClass = boardClass;
	this.row = row;
	this.col = col;
	this.td = td;
	this.value = '';
	this.const = false;
	this.block = Math.ceil(this.col / 3) + (3 * (Math.ceil(this.row / 3) - 1));
	this.siblings = {row: [], col: [], block: []};
	this.candidates = [];
	this.excluded = [];

	if (!boardClass.rows[ this.row ]) boardClass.rows[ this.row ] = [];
	boardClass.rows[ this.row ].push(this);
	if (!boardClass.cols[ this.col ]) boardClass.cols[ this.col ] = [];
	boardClass.cols[ this.col ].push(this);
	if (!boardClass.blocks[ this.block ]) boardClass.blocks[ this.block ] = [];
	boardClass.blocks[ this.block ].push(this);

	if (this.row == 4 || this.row == 7)
		this.td.parent().addClass('block-top');
	if (this.col == 4 || this.col == 7)
		this.td.parent().addClass('block-left');

//		this.td.html((this.row - 1) * 9 + this.col);
//	this.td.html('&nbsp;');
	this.td.click(function(e) { self.onclick(e); });

}
SudokuCell.prototype = {
	findSiblings: function()
	{
		var self = this;
		this.boardClass.each(function(cell){
			if (cell === self) return;
			if (cell.row == self.row) self.siblings.row.push(cell);
			if (cell.col == self.col) self.siblings.col.push(cell);
			if (cell.block == self.block) self.siblings.block.push(cell);
		});
	},
	onclick: function(e)
	{
//			this.debugHightlight(); return; // DEBUG
//		this.td.addClass('highlight');
//		this.td.prepend('<div class="overlay">q</div>');

		if (this.boardClass.excludeMode) {
			var excluded = prompt('Введите все исключаемые значения (через запятую без пробелов)', this.excluded.join(','));
			if (excluded === null) return;
			excluded = $.trim(excluded);
			this.excluded = excluded ? excluded.split(',').map($.trim) : [];
			this.boardClass.disableExcludeMode();
			this.boardClass.recalc();
			return;
		}

		var defaultVal = this.candidates.length == 1 ? this.candidates[0] : '';
		var val = prompt('Cell value (int) [' + this.candidates.join(', ') + ']:', defaultVal);
		if (val === null) return;

		if (val) {
			if (/^\d$/.test(val)) {
				this.val(val);
				if (this.boardClass.constructMode)
					this.const = true;
			} else {
				alert('Неверное значение. Допускаются числа 1-9');
			}
		} else {
			this.val('');
			if (this.boardClass.constructMode)
				this.const = false;
			log('set empty val to [' + this.col + ', ' + this.row + ']');
		}
		this.boardClass.recalc();
	},
	val: function(val)
	{
		if (val === '') {
			this.value = '';
			this.updateView();
			return this;
		} else if (val) {
			val = parseInt(val);
			if ($.inArray(val, this.candidates) > -1) {
				this.value = val;
				this.updateView();
			} else {
				alert('Значение уже встречается в строке, столбце или блоке');
			}
			return this;
		} else {
			return this.value;
		}
	},
	update: function()
	{
		var candidates = {};
		var i, val;

		for (i = 1; i <= 9; i++)
			candidates[i] = true;

		for (i = 0; i < this.excluded.length; i++)
			candidates[ this.excluded[i] ] = false;

		for (i = 0; i < this.siblings.row.length; i++) {
			val = this.siblings.row[i].val();
			if (val) candidates[val] = false;
		}
		for (i = 0; i < this.siblings.col.length; i++) {
			val = this.siblings.col[i].val();
			if (val) candidates[val] = false;
		}
		for (i = 0; i < this.siblings.block.length; i++) {
			val = this.siblings.block[i].val();
			if (val) candidates[val] = false;
		}

		this.candidates = [];
		for (i = 1; i <= 9; i++)
			if (candidates[i])
				this.candidates.push(i);

		this.updateView();
	},
	updateView: function()
	{
		if (this.value) {
			this.td.html(this.const ? '<div class="const">' + this.value + '</div>' : this.value);
		} else {
			if (this.boardClass.showCandidates) {
				this.td.empty();
				var cssClass = 'candidate';
				if (this.candidates.length == 1)
					cssClass = 'candidate-1';
				else if (this.candidates.length == 2)
					cssClass = 'candidate-2';

				for (var i = 0; i < this.candidates.length; i++)
					this.td.append('<div class="' + cssClass + '">' + this.candidates[i] + '</div>');
			} else {
				this.td.html('&nbsp;');
			}
		}
	},
	importData: function(data)
	{
		this.val(data.val);
		this.const = data.isConst;
	},
	exportData: function()
	{
		return {
			val: this.value,
			isConst: this.const ? 1 : 0
		};
	},
	debugHightlight: function()
	{
		var i;
		for (i = 0; i < this.siblings.row.length; i++)
			this.siblings.row[i].td.css('background', 'red');
		for (i = 0; i < this.siblings.col.length; i++)
			this.siblings.col[i].td.css('background', 'blue');
		for (i = 0; i < this.siblings.block.length; i++)
			this.siblings.block[i].td.css('background', 'green');
	}
};

function log(msg)
{
	$('#log').append('<div>' + msg + '</div>');
}