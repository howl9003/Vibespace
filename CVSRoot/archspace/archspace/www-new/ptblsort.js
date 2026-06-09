/***************************************************************************/
/* Priority-based Sortable Table                                           */
/* ptblsort.js                                                             */
/***************************************************************************/

/*-
 * Copyright (c) 2006 Archspace Development Team
 * All rights reserved.
 *
 * This code is derived from software contributed to Archspace Development Team
 * by
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this software
 *    must display the following acknowledgement:
 *        This product includes software developed by the Archspace Development Team
 *        and its contributors.
 * 4. Neither the name of the Archspace Development Team nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE ARCHSPACE DEVELOPMENT TEAM AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE FOUNDATION OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */


/****** Column class ******/

var ColumnData =
{
	TYPE : 0,
	VISIBLE : 1,
	HEADER_HTML : 2,
	CELL_LEFT_HTML : 3,
	CELL_RIGHT_HTML : 4,
	SIZE : 5
}

var DataTypes =
{
	NUMERIC : 0,
	STRING : 1,
	MAX : 1
}

function Column (type, visible, headerHTML, leftCellHTML, rightCellHTML)
{
	this._Definition = new Array(ColumnData.SIZE);
	this._Definition[ColumnData.TYPE] = type;
	this._Definition[ColumnData.VISIBLE] = visible;
	this._Definition[ColumnData.HEADER_HTML] = headerHTML;
	this._Definition[ColumnData.CELL_LEFT_HTML] = leftCellHTML;
	this._Definition[ColumnData.CELL_RIGHT_HTML] = rightCellHTML;

	Column.prototype.getDef = function (index)
	{
		return this._Definition[index];
	}

	Column.prototype.setDef = function (index, data)
	{
		this._Definition[index] = data;
		return true;
	}
}

/****** Row class ******/

function Row (RowDataArray)
{
	this._RowData = new Array();
	this._RowData = RowDataArray;

	Row.prototype.setCellData = function (index, data)
	{
		return this._RowData[index] = data;
	}

	Row.prototype.getCellData = function (index)
	{
		return this._RowData[index];
	}
}

/****** Priority Table class ******/

var SortOrders =
{
	NONE : -1,
	ASCENDING : 0,
	DESCENDING : 1
}

function PriorityTable ()
{
	this._PriorityRankTable = new Array(); // column id, rank, sort_order
	this._RanksAssigned = 0;
	this._TotalColumns = 0;

	PriorityTable.prototype.addColumn = function ()
	{
		this._PriorityRankTable[this._TotalColumns] = new Array(3);
		this._PriorityRankTable[this._TotalColumns][0] = this._TotalColumns;
		this._PriorityRankTable[this._TotalColumns][1] = -1;
		this._PriorityRankTable[this._TotalColumns][2] = SortOrders.NONE;
		this._TotalColumns++;
	}

	PriorityTable.prototype.setNextPriorityRank = function (column, sortingOrder)
	{
		if ((column < 0) || (column > this._TotalColumns)) return false;
		this._PriorityRankTable[column][1] = this._RanksAssigned;
		this._PriorityRankTable[column][2] = sortingOrder;
		this._RanksAssigned++;
		return true;
	}

	PriorityTable.prototype.removePriorityRank = function (column)
	{
		if ((column < 0) || (column > this._TotalColumns)) return false;
		if (this._PriorityRankTable[column][1] == -1) return false;
		var removedRank = this._PriorityRankTable[column][1];
		this._PriorityRankTable[column][1] = -1;
		this._PriorityRankTable[column][2] = SortOrders.NONE;
		for (var col = 0; col < this._TotalColumns; col++)
		{
			if (this._PriorityRankTable[col][1] > removedRank) this._PriorityRankTable[col][1]--;
		}
		this._RanksAssigned--;
		return true;
	}

	PriorityTable.prototype.getPriorityRank = function (rank)
	{
		if ((rank > PriorityTable._RanksAssigned) || (rank < 0)) return false;
		for (var col = 0; col < this._TotalColumns; col++)
		{
			if (this._PriorityRankTable[col][1] == rank) return col;
		}
		return -1;
	}

	PriorityTable.prototype.getRankByColumn = function (column)
	{
		return this._PriorityRankTable[column][1];
	}

	PriorityTable.prototype.getRanksAssigned = function ()
	{
		return this._RanksAssigned;
	}

	PriorityTable.prototype.getSortingOrder = function (column)
	{
		if (column > -1) return this._PriorityRankTable[column][2];
		else return -1;
	}

	PriorityTable.prototype.setSortingOrder = function (column, sortingOrder)
	{
		this._PriorityRankTable[column][2] = sortingOrder;
		return true;
	}

	PriorityTable.prototype.isRanked = function (column)
	{
		if (this._PriorityRankTable[column][1] > -1) return true;
		else return false;
	}
}

/****** Table class ******/

function Table ()
{
	this._TableAttributes = "";
	this._HeadRowAttributes = "";
	this._RowAttributes = "";
	this._Columns = new Array();
	this._Rows = new Array();
	this._TotalRows = 0;
	this._TotalColumns = 0;
	this._PrioritySet = new PriorityTable();
	this._SortDelimiter = "";

	Table.prototype.setTableAttributes = function (attributes)
	{
		this._TableAttributes = attributes;
	}

	Table.prototype.setHeaderRowAttributes = function (attributes)
	{
		this._HeaderRowAttributes = attributes;
	}

	Table.prototype.setRowAttributes = function (attributes)
	{
		this._RowAttributes = attributes;
	}

	Table.prototype.setSortDelimiter = function (delimiter)
	{
		this._SortDelimiter = delimiter;
	}

	Table.prototype.addColumn = function (type, visible, headerHTML, leftCellHTML, rightCellHTML)
	{
		this._Columns[this._TotalColumns] = new Column(type, visible, headerHTML, leftCellHTML, rightCellHTML);
		this._PrioritySet.addColumn();
		this._TotalColumns++;
	}

	Table.prototype.setColumnData = function (columnIndex, defIndex, data)
	{
		return this._Columns[columnIndex].setDef(defIndex, data);
	}

	Table.prototype.getColumnData = function (column, def)
	{
		//this._Columns[column].getDef(def);
		return this._Columns[column]._Definition[def];
	}

	Table.prototype.getTotalColumns = function ()
	{
		return this._TotalColumns;
	}

	Table.prototype.getTotalRows = function ()
	{
		return this._TotalRows;
	}

	Table.prototype.addRow = function (delimiter, data)
	{
		this._Rows[this._TotalRows] = new Row(data.split(delimiter));
		this._TotalRows++;
	}

	Table.prototype.addRows = function (delimiter, data)
	{
		dataarray = new Array();
		dataarray = data;
		for (row = 0; row < dataarray.length; row++)
		{
			this._Rows[this._TotalRows] = new Row(dataarray[row].split(delimiter));
			this._TotalRows++;
		}
	}

	Table.prototype.setCell = function (row, column, data)
	{
		return this._Rows[row].setCellData[column] = data;
	}

	Table.prototype.getCell = function (row, column)
	{
		//return this._Rows[row].getCellData(column);
		return this._Rows[row]._RowData[column];
	}

	Table.prototype.addPriorityRank = function (column, sortingOrder)
	{
		return this._PrioritySet.setNextPriorityRank(column, sortingOrder);
	}

	Table.prototype.removePriorityRank = function (column)
	{
		return this._PrioritySet.removePriorityRank (column);
	}

	Table.prototype.getPriorityRank = function (column)
	{
		return this._PrioritySet.getRankByColumn(column);
	}

	Table.prototype.getRanksAssigned = function ()
	{
		return this._PrioritySet.getRanksAssigned();
	}

	Table.prototype.getSortingOrder = function (column)
	{
		return this._PrioritySet.getSortingOrder(column);
	}

	Table.prototype.setSortingOrder = function (column, sortingOrder)
	{
		return this._PrioritySet.setSortingOrder(column, sortingOrder);
	}

	Table.prototype.sort = function ()
	{
		var TableData = new Array();
		var RowOrder = new Array(this._TotalRows);
		for (var row = 0; row < this._TotalRows; row++)
		{
			RowOrder[row] = row;
		}
		var SortedRows = new Array(this._TotalRows);
		SortedRows = this.getSortedRows(RowOrder, 0);
		TempTableRows = new Array(this._TotalRows);
		for (var row = 0; row < this._TotalRows; row++)
		{
			TempTableRows[row] = this._Rows[row];
		}
		for (var row = 0; row < this._TotalRows; row++)
		{
			this._Rows[row] = TempTableRows[SortedRows[row]];
		}
	}

	Table.prototype.getTableHTML = function ()
	{
		var TableHTML = new String();
		TableHTML += "\t\t<table " + this._TableAttributes + ">\n";
		//Header Row
		if (this._HeaderRowAttributes) TableHTML += "\t\t\t<tr " + this._HeaderRowAttributes + ">\n";
		else TableHTML += "\t\t\t<tr>\n";
		for (var col = 0; col < this._TotalColumns; col++)
		{
			if (this._Columns[col]._Definition[ColumnData.VISIBLE])
			{
				TableHTML += "\t\t\t\t" + this._Columns[col]._Definition[ColumnData.HEADER_HTML] + "\n";
			}
		}
		TableHTML += "\t\t\t</tr>\n";
		//Table Rows
		for (var row = 0; row < this._TotalRows; row++)
		{
			if (this._RowAttributes) TableHTML += "\t\t\t<tr " + this._RowAttributes + ">\n";
			else TableHTML += "\t\t\t<tr>\n";
			for (var col = 0; col < this._TotalColumns; col++)
			{
				if (this._Columns[col]._Definition[ColumnData.VISIBLE])
				{
					TableHTML += "\t\t\t\t" + this._Columns[col]._Definition[ColumnData.CELL_LEFT_HTML] + stringReplaceAll(this._Rows[row]._RowData[col], this._SortDelimiter, "") + this._Columns[col]._Definition[ColumnData.CELL_RIGHT_HTML] + "\n";
				}
			}
			TableHTML += "\t\t\t</tr>\n";
		}
		TableHTML += "\t\t</table>\n"
		return TableHTML;
	}

	Table.prototype.getSortedRows = function (Rows, depth)
	{
		if ((this._PrioritySet.getPriorityRank(depth) > -1) && (depth < this._TotalColumns))
		{
			var SortedUniques = new Array();
			SortedUniques = this.getSortedUniqueValuesArray(Rows, this._PrioritySet.getPriorityRank(depth));
			var SortedRows = new Array();
			var SortedRowCount = 0;
			for (var su = 0; su < SortedUniques.length; su++)
			{
				if (SortedUniques[su][1] > 1 && this._PrioritySet.getPriorityRank((depth + 1)) > -1)
				{
					var next = this.getSortedUniqueValuesArray(this.getMatchingRows(Rows, SortedUniques[su][0], this._PrioritySet.getPriorityRank(depth)), this._PrioritySet.getPriorityRank(depth));
					if (next[0][1] > 1)
					{
						var TempRowArray = new Array(next[0][1].length);
						TempRowArray = this.getSortedRows(this.getMatchingRows(Rows, next[0][0], this._PrioritySet.getPriorityRank(depth)), (depth + 1));
						for (var temprow = 0; temprow < TempRowArray.length; temprow++)
						{
							SortedRows[SortedRowCount] = TempRowArray[temprow];
							SortedRowCount++;
						}
					}
				}
				else if ((SortedUniques[su][1] > 1) && (this._PrioritySet.getPriorityRank((depth + 1)) <= -1))
				{
					var TempArray = this.getMatchingRows(Rows, SortedUniques[su][0], this._PrioritySet.getPriorityRank(depth));
					for (var rep = 0; rep < TempArray.length; rep++)
					{
						SortedRows[SortedRowCount] = TempArray[rep];
						SortedRowCount++;
					}
				}
				else if (SortedUniques[su][1] == 1)
				{
					SortedRows[SortedRowCount] = this.getMatchingRows(Rows, SortedUniques[su][0], this._PrioritySet.getPriorityRank(depth));
					var Tempy = new String(SortedRows[SortedRowCount]);
					SortedRowCount++;
				}
				else
				{
					alert("Error.." + SortedUniques[su][1])
				}

			}
			return SortedRows;
		}
		else
		{
			return Rows;
		}
	}

	Table.prototype.getMatchingRows = function (Rows, unique, column)
	{
		var MatchingRows = new Array();
		var MatchedRows = 0;
		for (var row = 0; row < Rows.length; row++)
		{
			if (this.getSortedCharacterRange(this._Rows[Rows[row]]._RowData[column]) == unique)
			{
				MatchingRows[MatchedRows] = Rows[row];
				MatchedRows++;
			}
		}
		return MatchingRows;
	}

	Table.prototype.getSortedUniqueValuesArray = function (RowIndexArray, column)
	{
		var TempUniqueArray = new Array();
		var UniqueValues = new Array(); // value, counts
		var TotalUniqueValues = 0;
		var Exists = false;
		//create temporary single-dimension array to sort.
		for (var row = 0; row < RowIndexArray.length; row++)
		{

			Exists = false;
			for (var i = 0; i < TotalUniqueValues; i++)
			{
				if (this.getSortedCharacterRange(this._Rows[RowIndexArray[row]]._RowData[column]) == TempUniqueArray[i])
				{
					Exists = true;
					i = TotalUniqueValues;
				}
			}
			if (!Exists)
			{
				TempUniqueArray[TotalUniqueValues] = this.getSortedCharacterRange(this._Rows[RowIndexArray[row]]._RowData[column]);
				TotalUniqueValues++;
			}
		}
		if ((this.getColumnData(column, ColumnData.TYPE) == DataTypes.NUMERIC) && (this._PrioritySet.getSortingOrder(column) == SortOrders.ASCENDING))
		{
			//Change TempUniqueArray to new Array();
			TempUniqueArray.sort(this.numericSortAsc);
		}
		else if ((this.getColumnData(column, ColumnData.TYPE) == DataTypes.NUMERIC) && (this._PrioritySet.getSortingOrder(column) == SortOrders.DESCENDING))
		{
			TempUniqueArray.sort(this.numericSortDesc);
		}
		else
		{
			TempUniqueArray.sort();
			if (this._PrioritySet.getSortingOrder(column) == SortOrders.DESCENDING)
			{
				TempUniqueArray.reverse();
			}
		}
		//copy sorted values over to main array.
		for (var i = 0; i < TotalUniqueValues; i++)
		{
			UniqueValues[i] = new Array(2);
			UniqueValues[i][0] = TempUniqueArray[i];
			UniqueValues[i][1] = 0;
		}
		//get repeats
		for (var row = 0; row < RowIndexArray.length; row++)
		{
			for (var i = 0; i < TotalUniqueValues; i++)
			{
				if (this.getSortedCharacterRange(this._Rows[RowIndexArray[row]]._RowData[column]) == UniqueValues[i][0])
				{
					UniqueValues[i][1]++;
					i = TotalUniqueValues;
				}
			}
		}
		return UniqueValues;
	}

	Table.prototype.getSortedCharacterRange = function (celldata)
	{
		if (typeof(celldata) != "string") return celldata;
		if ((typeof(this._SortDelimiter) != "string") || (this._SortDelimiter.length <= 0)) return celldata;
		if (celldata.indexOf(this._SortDelimiter) != celldata.lastIndexOf(this._SortDelimiter))
		{
			return celldata.substring(celldata.indexOf(this._SortDelimiter) + this._SortDelimiter.length, celldata.lastIndexOf(this._SortDelimiter));
		}
		return celldata;
	}

	Table.prototype.numericSortAsc = function (a, b) { return (a-b); }
	Table.prototype.numericSortDesc = function (a, b) { return (b-a); }

}

/*********** Utility functions **********/

function stringReplaceAll( str, from, to ) {
    if ((typeof(str) != "string") || (typeof(from) != "string") || (typeof(str) != "string")) return str;
    var idx = str.indexOf( from );
    while ( idx > -1 ) {
	str = str.replace( from, to );
	idx = str.indexOf( from );
    }

    return str;
}

/****************************************/