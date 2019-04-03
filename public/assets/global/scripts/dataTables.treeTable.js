/*! TreeTable 0.0.1-dev
 */

/**
 * @summary     TreeTable
 * @description 
 * @version     0.0.1-dev
 * @file        dataTables.treeTable.js
 * @author      Shingo Sugimoto
 * @contact     http://
 *
 * This source file is free software, available under the following license:
 *   MIT license
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the license files for details.
 *
 */

(function(window, document, $) {

var factory = function($, DataTable) {
"use strict";

var TreeTable = function(settings, opts) {

	if (!DataTable.versionCheck || !DataTable.versionCheck('1.10.8')) {
		throw 'DataTables TreeTable requires DataTables 1.10.8 or newer';
	}

	this.s = {
		dt: new DataTable.Api(settings)
	};

	if (settings._treeTable) {
		return this.s.dt.settings()[0]._treeTable;
	}

	if (opts && typeof opts.details === 'string') {
		opts.details = { type: opts.details };
	}

	this.c = $.extend(true, {}, TreeTable.defaults, DataTable.defaults.treetable, opts);
	settings._treeTable = this;

	this.dom = {
    table: null,
    treetable: null,
  };

	this.pls = {};

	this._constructor();
};

$.extend(TreeTable.prototype, {

	_constructor: function ()
	{
		var self = this;
		var dt = this.s.dt;
		var dtSettings = self.s.dt.settings()[0];

		self.dom.table = $('table', dt.table().container()).addClass('dt-treetable');

		var table = dtSettings.oInstance;
		var $table = self.dom.table;

    // sourced data
    $(dtSettings.aoData.reverse()).each(function() {
      var data = this;
		
      var rowdata = self._arrayToObject(data._aData);
      rowdata = self._getTTData(rowdata, data.nTr);

      if (self._has(self.pls, rowdata[self.c.nodeIdAttr]))
        rowdata[self.c.branchAttr] = true;

      var parent = rowdata[self.c.parentIdAttr];
      if (!parent) parent = null;
      if (!self._has(self.pls, parent)) self.pls[parent] = [];
      self.pls[parent].unshift(rowdata);
    } );

    dt.clear();

    // new tree node (tr)
    var createdRow = function (row, data) {
      var $row = $(row);

			//console.log(self);

      var parent = $table.treetable('node', data[self.c.parentIdAttr]);
      if (!parent) parent = null;

      if (self._has(data, self.c.nodeIdAttr)) {

  			$row.attr(self._attrName(self.c.nodeIdAttr), data[self.c.nodeIdAttr]);

        if (data[self.c.parentIdAttr])
          $row.attr(self._attrName(self.c.parentIdAttr), data[self.c.parentIdAttr]);

        if (data[self.c.branchAttr])
          $row.attr(self._attrName(self.c.branchAttr), data[self.c.branchAttr]);

        $table.treetable('loadBranch', parent, row);
      }
    };
    dtSettings.aoRowCreatedCallback.push({fn: createdRow, sName: "treetable"});

    // treetable
    var ttopts = self.c;
    //ttopts['_onNodeInitialized'] = ttopts['onNodeInitialized'];
    ttopts['_onNodeCollapse'] = ttopts['onNodeCollapse'];
    ttopts['_onNodeExpand'] = ttopts['onNodeExpand'];

    // treetable #collapse
    var removeRow = function (idx, child) {
      if (self._isArray(child.children) && child.children) $.each(child.children, removeRow);
      table.fnDeleteRow(child.row);
    };
    ttopts['onNodeCollapse'] = function () {
	  var node = this;
      var page = table.fnTreePagingInfo().iPage;
      $.each(node.children, removeRow);
      if (typeof ttopts['_onNodeCollapse'] === 'function') ttopts['_onNodeCollapse'].apply(this, arguments);
      table.fnPageChange(page);
    };

    // treetable #expand
    ttopts['onNodeExpand'] = function () {
      var node = this;
      if (self._has(self.pls, node.id))
      {
        var items = self.pls[node.id];
        self.addChildren(node, items);
      }
      if (typeof ttopts['_onNodeExpand'] === 'function') ttopts['_onNodeExpand'].apply(this, arguments);
	  //***********************************
	  checkCheckboxes(this.id);
	  //***********************************
    };

    // init treetable
    this.dom.treetable = $table.treetable(ttopts);

    // root nodes restore
    if (self._has(self.pls, null) && self._isArray(self.pls[null])) {
      dt.rows.add(self.pls[null]);
    }
  },

	addChildren: function (node, items)
	{
		var self = this;

		//console.log(self);

		var dtSettings = self.s.dt.settings()[0];
		var table = dtSettings.oInstance;

    if (self._isArray(items) && items.length) {
      var page = table.fnTreePagingInfo().iPage;
      $.each(items, function (idx, item) {
        table.fnAddTreeDataAndDisplayPosition(item, node);
      } );
      table.fnPageChange(page);
    }
  },

  _getTTData: function(data, row) {
    data = data || {};
    var self = this;
    var $row = $(row);

    if (!data[self.c.nodeIdAttr] && $row.data(self.c.nodeIdAttr)) {
      data[self.c.nodeIdAttr] = $row.data(self.c.nodeIdAttr).toString();
    }

    if (self._has(data, self.c.nodeIdAttr))
    {
      if (!data[self.c.parentIdAttr] && $row.data(self.c.parentIdAttr)) {
        data[self.c.parentIdAttr] = $row.data(self.c.parentIdAttr).toString();
      }

      if ($row.data(self.c.branchAttr)) {
        data[self.c.branchAttr] = ($row.data(self.c.branchAttr) === 'true' || $row.data(self.c.branchAttr) === true);
      }
      else if (data[self.c.branchAttr]) {
        data[self.c.branchAttr] = (data[self.c.branchAttr] === 'true' || data[self.c.branchAttr] === true);
      }
    }

    return data;
  },

  _has: function(obj, key) {
    return obj != null && hasOwnProperty.call(obj, key);
  },

  _isArray: function(obj) {
    return toString.call(obj) === '[object Array]';
  },

  _isObject: function(obj) {
    var type = typeof obj;
    return type === 'function' || type === 'object' && !!obj;
  },

  _attrName: function(key) {
    return "data-" + key.replace(/([A-Z])/g, "-$1").toLowerCase();
  },

  _arrayToObject: function(array) {
    return (this._isArray(array)) ? array.reduce(function(o, v, i) { o[i] = v; return o; }, {}) : array;
  }

} );

TreeTable.defaults = {
  branchAttr: "ttBranch",
  expandable: true,
  nodeIdAttr: "ttId",
  parentIdAttr: "ttParentId",
};

var Api = $.fn.dataTable.Api;

/*Api.register('treeTable.addChildren()', function (node, data) {
  return this.iterator('table', function (ctx) {
    if (ctx._treeTable._isArray(data))
      ctx._treeTable.addChildren(node, data);
  } );
} );

Api.register('treeTable.addChild()', function (node, data) {
  return this.iterator('table', function (ctx) {
  	console.log(ctx);
    if (ctx._treeTable._isObject(data))
      ctx._treeTable.addChildren(node, [data]);
  } );
} );*/

TreeTable.version = '0.0.1-dev';

//$.fn.dataTable.TreeTable = TreeTable;
//$.fn.DataTable.TreeTable = TreeTable;

$(document).on('preInit.dt.dtr', function (e, settings) {
	if (e.namespace !== 'dt') return;

	if ($(settings.nTable).hasClass('treetable') ||
		 $(settings.nTable).hasClass('dt-treetable') ||
		 settings.oInit.treetable ||
		 DataTable.defaults.treetable
	) {
		var init = settings.oInit.treetable;

		if (init !== false) {
			new TreeTable(settings, $.isPlainObject(init) ? init : {});
		}
	}
});

return TreeTable;
}; // /factory

	if (typeof define === 'function' && define.amd) {
		define(['jquery', 'datatables', 'treetable'], factory);
	}
	else if (typeof exports === 'object') {
		factory(require('jquery'), require('datatables'), require('treetable'));
	}
	else if (jQuery && !jQuery.fn.dataTable.TreeTable) {
		factory(jQuery, jQuery.fn.dataTable);
	}

})(window, document);

if (jQuery)
{
jQuery.fn.dataTableExt.oApi.fnAddTreeDataDisplayPosition = function (oSettings, aDataIn, iPos) {
	var iRow = oSettings.aoData.length;
	var oData = $.extend( true, {}, $.fn.dataTable.models.oRow, {
		src: 'data'
	} );
	oData._aData = aDataIn;
	oSettings.aoData.push(oData);
	oSettings.aiDisplayMaster.splice(iPos, 0, iRow);
	return iRow;
};

jQuery.fn.dataTableExt.oApi.fnAddTreeDataAndDisplayPosition = function (oSettings, aData, aParent) {
  var tt = oSettings._treeTable;
  var aPos = -1;
  for (var i=0, iLen=oSettings.aoData.length ; i<iLen ; i++) {
    if (oSettings.aoData[i]._aData[tt.c.nodeIdAttr] == aParent.id) {
      aPos = oSettings.aiDisplayMaster.indexOf(i);
      break;
    }
  }

  var iAdded = this.oApi.fnAddTreeDataDisplayPosition(oSettings, aData, aPos+1);
  var nAdded = oSettings.aoData[iAdded].nTr;

  this.oApi._fnReDraw(oSettings);

  var iPos = -1;
  for (var i=0, iLen=oSettings.aiDisplay.length ; i<iLen ; i++) {
    if (oSettings.aoData[oSettings.aiDisplay[i]].nTr == nAdded) {
      iPos = i;
      break;
    }
  }
  if (iPos >= 0)
  {
    oSettings._iDisplayStart = (Math.floor(i / oSettings._iDisplayLength)) * oSettings._iDisplayLength;
    if (this.oApi._fnCalculateEnd)
      this.oApi._fnCalculateEnd(oSettings);
  }

  this.oApi._fnDraw(oSettings);
  return {
    "nTr": nAdded,
    "iPos": iAdded
  };
};

jQuery.fn.dataTableExt.oApi.fnTreePagingInfo = function (oSettings) {
  return {
    "iStart": oSettings._iDisplayStart,
    "iEnd": oSettings.fnDisplayEnd(),
    "iLength": oSettings._iDisplayLength,
    "iTotal": oSettings.fnRecordsTotal(),
    "iFilteredTotal": oSettings.fnRecordsDisplay(),
    "iPage": Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
    "iTotalPages": Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
  };
};

}
