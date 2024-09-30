var baseURL = $('#base-url').val();

userRequest = function(idmail, idusr, role){
	
	$('#' + idusr + ' .btn').prop('disabled', true);
	var txt_btn = $('#' + idusr + ' .btn-action-user' + role)[0].innerHTML; 
	$('#' + idusr + ' .btn-action-user' + role)[0].innerHTML = 'Sending...';

	$.ajax({
    	type: "POST",
        url: baseURL + "applib/changeTypeOfUser.php",
        data: 'id=' + idmail + '&t=' + role + '&ot=100', 
        success: function(data) {
			d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
			$('#' + idusr + ' .btn').prop('disabled', false);
			$('#' + idusr + ' .btn-action-user' + role)[0].innerHTML = txt_btn;
            if(d == '1'){
				$('#' + idusr).remove();
				if($('.mt-action').length == 0) {
					$('.mt-actions').append('<div class="mt-action">No pending requests</div>');
				}

			}else{
				$('#myModal1').modal('show');	
			}
		}
    });

}

var Dashboard = function() {

    return {

      handleTable:function () {

          function restoreRow(oTable, nRow) {
              var aData = oTable.fnGetData(nRow);
              var jqTds = $('>td', nRow);

              for (var i = 0, iLen = jqTds.length; i < iLen; i++) {
                  oTable.fnUpdate(aData[i], nRow, i, false);
              }

              oTable.fnDraw();
          }

          function editRow(oTable, nRow) {
              var aData = oTable.fnGetData(nRow);
              var jqTds = $('>td', nRow);
              jqTds[6].innerHTML = '<input type="number" class="form-control input-xsmall input-sm" min="1" max="100" value="' + aData[6] + '">';
              jqTds[7].innerHTML = '<div class="btn-group" style="margin-top:1px;">' +
                '<button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i>' +
                '</button>' +
                '<ul class="dropdown-menu pull-right" role="menu">' +
                '<li><a class="edit" href="javascript:;"><i class="fa fa-save"></i> Save </a></li>' +
                '<li><a class="cancel" href="javascript:;"><i class="fa fa-times-circle"></i> Cancel edition</a></li>' +
                '</ul>' +
                '</div>';
          }

          function saveRow(oTable, nRow) {
              var jqInputs = $('input', nRow);
              var jqSelects = $('select', nRow);
              oTable.fnUpdate(jqInputs[0].value, nRow, 6, false);
              oTable.fnUpdate('<div class="btn-group" style="margin-top:1px;">' +
                              '<button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></button>' +
                              '<ul class="dropdown-menu pull-right" role="menu">' +
                              '<li><a class="edit" href="javascript:;"><i class="fa fa-pencil"></i> Change Disk Quota </a></li>' +
                              '</ul>' +
                              '</div>', nRow, 7, false);
              oTable.fnDraw();
          }

          var table = $('#sample_editable_1');

          var oTable = table.dataTable({

              // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
              // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js).
              // So when dropdowns used the scrollable div should be removed.
              //"dom": "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r>t<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",

              "lengthMenu": [
                  [5, 15, 20, -1],
                  [5, 15, 20, "All"] // change per page values here
              ],

              // Or you can use remote translation file
              //"language": {
              //   url: '//cdn.datatables.net/plug-ins/3cfcc339e89/i18n/Portuguese.json'
              //},

              // set the initial value
              "pageLength": 5,

              "language": {
                  "lengthMenu": " _MENU_ records"
              },
              "columnDefs": [{ // set default column settings
                  'orderable': true,
                  'targets': [0]
              }, {
                  "searchable": true,
                  "targets": [0]
              },{ // set default column settings
                  'orderable': false,
                  'targets': [7]
              }, {
                  "searchable": false,
                  "targets": [7]
              }],
              "order": [
                  [5, "desc"]
              ] // set first column as a default sort by asc
          });

          var tableWrapper = $("#sample_editable_1_wrapper");

          var nEditing = null;
          var nNew = false;

          table.on('click', '.cancel', function (e) {
              e.preventDefault();
              if (nNew) {
                  oTable.fnDeleteRow(nEditing);
                  nEditing = null;
                  nNew = false;
              } else {
                  restoreRow(oTable, nEditing);
                  nEditing = null;
              }
          });

          table.on('click', '.edit', function (e) {
              e.preventDefault();

              /* Get the row as a parent of the link that was clicked on */
              var nRow = $(this).parents('tr')[0];

              if (nEditing !== null && nEditing != nRow) {
                  if(nNew){
                    $('#myModal3').modal('show');
                  }else{
                    /* Currently editing - but not this row - restore the old before continuing to edit mode */
                    restoreRow(oTable, nEditing);
                    editRow(oTable, nRow);
                    nEditing = nRow;
                    nNew = false;
                  }

              } else if (nEditing == nRow && this.innerHTML.indexOf("Save") != -1 ) {
                  /* Editing this row and want to save it */
                  var jqInputs = $('input', nRow);
                  var jqTds = $('>td', nRow);

                  saveRow(oTable, nEditing);
                  nEditing = null;
                  nNew = false;

                  // ajax with this!!!
                  var strData = 'id=' + jqTds[0].innerText + '&disk=' + jqInputs[0].value;
                  strData = strData.replace(/ /g, '+');
					
				  var actionsButton = $('button', $('td:last', nRow)[0]);
				  actionsButton[0].innerHTML = 'Sending...';
				  actionsButton.prop('disabled', true);

				  $.ajax({
           			type: "POST",
           			url: baseURL + "applib/modifyUserDiskQuota.php",
           			data: strData, 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
						}else{
							$('#myModal1').modal('show');	
						}
						actionsButton[0].innerHTML = 'Actions <i class="fa fa-angle-down"></i>';
						actionsButton.prop('disabled', false);
					}
         		  });

              } else {
                  /* No edit in progress - let's start one */
                  editRow(oTable, nRow);
                  nEditing = nRow;
                  nNew = false;
              }
          });
      },

      initKNOB: function () {
          //knob does not support ie8 so skip it
          if (!jQuery().knob || App.isIE8()) {
              return;
          }

          // general knob
          $(".knob").knob({
              'dynamicDraw': true,
              'thickness': 0.4,
              'tickColorizeValues': true,
              'skin': 'tron',
              'format' : function (value) {
                 return value + '%';
              },
              'draw': function() {
                $(this.i).css('font-size', '30px');
                $(this.i).css('font-weight', 'normal');
                $(this.i).css('font-family', '"Open Sans",sans-serif');
              }
          });
      },

        initCharts: function() {
            if (!jQuery.plot) {
                return;
            }

            function showChartTooltip(x, y, xValue, yValue) {
                $('<div id="tooltip" class="chart-tooltip">' + yValue + '<\/div>').css({
                    position: 'absolute',
                    display: 'none',
                    top: y - 40,
                    left: x - 40,
                    border: '0px solid #ccc',
                    padding: '2px 6px',
                    'background-color': '#fff'
                }).appendTo("body").fadeIn(200);
            }

            var mailsList = [];

			$.ajax({
           			type: "POST",
           			url: baseURL + "applib/getMailData.php",
           			data: 'flag=1', 
           			success: function(data) {
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
					var aux = d.split(';');
					for(i = 0; i < aux.length; i ++){
						var aux2 = aux[i].split(',');
						if (aux2[1]){
							mailsList[i] = [];							
							mailsList[i].push(aux2[0].replace('[', ''));
							mailsList[i].push(aux2[1].replace(']', ''));
						}
					}
					fillPlotWithData();
				}
         		  });

            //if (($('#site_statistics').size() != 0) && (mailsList.length > 0)) {
            function fillPlotWithData() {
            	
                $('#site_statistics_loading').hide();
                $('#site_statistics_content').show();

                var plot_statistics = $.plot($("#site_statistics"), [{
                        data: mailsList,
                        lines: {
                            fill: 0.6,
                            lineWidth: 0
                        },
                        color: ['#5c9bd1']
                    }, {
                        data: mailsList,
                        points: {
                            show: true,
                            fill: true,
                            radius: 5,
                            fillColor: "#5c9bd1",
                            lineWidth: 3
                        },
                        color: '#ebf3f9',
                        shadowSize: 0
                    }],

                    {
                        xaxis: {
                            tickLength: 0,
                            tickDecimals: 0,
                            mode: "categories",
                            min: 0,
                            font: {
                                lineHeight: 14,
                                style: "normal",
                                variant: "small-caps",
                                color: "#6F7B8A"
                            }
                        },
                        yaxis: {
                            ticks: 5,
                            tickDecimals: 0,
                            tickColor: "#eee",
                            font: {
                                lineHeight: 14,
                                style: "normal",
                                variant: "small-caps",
                                color: "#6F7B8A"
                            }
                        },
                        grid: {
                            hoverable: true,
                            clickable: true,
                            tickColor: "#eee",
                            borderColor: "#eee",
                            borderWidth: 1
                        }
                    });

                var previousPoint = null;
                $("#site_statistics").bind("plothover", function(event, pos, item) {
                    $("#x").text(pos.x.toFixed(2));
                    $("#y").text(pos.y.toFixed(2));
                    if (item) {
                        if (previousPoint != item.dataIndex) {
                            previousPoint = item.dataIndex;

                            $("#tooltip").remove();
                            var x = item.datapoint[0].toFixed(2),
                                y = item.datapoint[1].toFixed(2);

                            showChartTooltip(item.pageX, item.pageY, item.datapoint[0], item.datapoint[1] + ' sendings');
                        }
                    } else {
                        $("#tooltip").remove();
                        previousPoint = null;
                    }
                });
            }

        },

		initEasyPieCharts: function() {
            if (!jQuery().easyPieChart) {
                return;
            }
			
            $('.easy-pie-chart .number.cpu_info').easyPieChart({
                animate: 1000,
                size: 75,
                lineWidth: 5,
                barColor: '#006b8f'
            });
		
			var ajaxCall = function() {
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/getCPUStats.php",
           			data: "flag=1", 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
						obj_cpus = JSON.parse(d);
						for(var k in obj_cpus){
							var total = obj_cpus[k]['user'] + obj_cpus[k]['nice'] + obj_cpus[k]['sys'];
							$('.easy-pie-chart .number.cpu_info.' + k).data('easyPieChart').update(total);
		                    $('span', '.easy-pie-chart .number.cpu_info.' + k).text(Math.round(total));
						}
						setTimeout(ajaxCall, 1000);
					}
         		  });
			}

	
            /*$('.easy-pie-chart-reload').click(function() {
				  ajaxCall();
            });*/

			 ajaxCall();

        },

		initSparklineCharts: function() {
            if (!jQuery().sparkline) {
                return;
            }
			
			var getMonths = function() {
				var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
				var d = new Date();
    			var m = d.getMonth();
				var cy = d.getYear() - 100;
				var py = cy - 1;
					
				var gmonths = [];
				for(var i = 0; i < months.length; i ++){
					gmonths.push(months[m+1] + "'" + py);
					if((m + 1) < (months.length - 1)) m ++;
					else { m = -1; py = cy; }
				}
				return gmonths;
			}
		
			var labelMonths = getMonths();
		
			$("#spark_registers").sparkline('html', {
                type: 'bar',
                width: '300',
                barWidth: 30,
                height: '120',
                barColor: '#006b8f',
				tooltipFormat: '{{offset:offset}}: {{value}}',
    			tooltipValueLookups: {
        			'offset': {
            			0: labelMonths[0],
            			1: labelMonths[1],
            			2: labelMonths[2],
           	 			3: labelMonths[3],
            			4: labelMonths[4],
            			5: labelMonths[5],
						6: labelMonths[6],
            			7: labelMonths[7],
            			8: labelMonths[8],
           	 			9: labelMonths[9],
            			10: labelMonths[10],
            			11: labelMonths[11]

        			}			
    			},	

            });

			// 	arreglar això a veure si agafa labeeeeels!

            $("#spark_types").sparkline('html', {
                type: 'pie',
                width: '120',
                height: '120',
                sliceColors: ['#006b8f', '#4dd2ff','#0099cc', '#004d66', '#00ace6'],
				tooltipFormat: '<span style="color: {{color}}">&#9679;</span> {{offset:names}} ({{percent.1}}%)',	
				tooltipValueLookups: {
            		names: labelsUsersPieChart
        		}	

            });

            $("#spark_disk").sparkline('html', {
                type: 'box',
                width: '200',
                height: '120',
                lineColor: '#006b8f',
				fillColor: '#ccf2ff',
				minSpotColor: '#000',
				maxSpotColor: '#000',
				highlightSpotColor: '#000',
				highlightLineColor: '#f08000'
            });

        },

		initDynCharts: function() {

            if (!jQuery.plot) {
                return;
            }

            var data = [];
			data[0] = [];
			data[1] = [];
            var totalPoints = [];
			totalPoints[0] = 150;
			totalPoints[1] = 150;

			function getMemoryData(id,options){
				
				$.ajaxSetup({ cache: false });

				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/getMemoryStats.php",
           			data: "flag=1", 
           			success: function(dataAjax) {
						d = dataAjax.replace(/(\r\n|\n|\r|\t)/gm,"");
						var res=[];
						if (data[id].length > 0) data[id] = data[id].slice(1);

						while (data[id].length < totalPoints[id]) {
							data[id].push(0);
						}
						data[id].push(d);
                		for (var i = 0; i < data[id].length; ++i) {
							res.push([i, data[id][i]]);
                		}
						$.plot($("#chart_4"), [res], options);
						setTimeout(function() { getMemoryData(0, options); }, 1000);
					},
					error: function() { console.log('error');  }
         		});	
			}
			
			function getCPUData(id,options){
				
				$.ajaxSetup({ cache: false });

				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/getCPUStats.php",
           			data: "flag=1", 
           			success: function(dataAjax) {
						d = dataAjax.replace(/(\r\n|\n|\r|\t)/gm,"");
						obj_cpus = JSON.parse(d);
						var average = 0;
						var sizeOfObject = 0;
						for(var k in obj_cpus){
							sizeOfObject ++;
							average += parseInt(obj_cpus[k]['user'] + obj_cpus[k]['nice'] + obj_cpus[k]['sys']);
						}
						average = average / sizeOfObject;
						var res=[];
						if (data[id].length > 0) data[id] = data[id].slice(1);

						while (data[id].length < totalPoints[id]) {
							data[id].push(0);
						}
						data[id].push(average);
                		for (var i = 0; i < data[id].length; ++i) {
							res.push([i, data[id][i]]);
                		}
						$.plot($("#chart_5"), [res], options);
						setTimeout(function() { getCPUData(1, options); }, 1000);

					},
					error: function() { console.log('error');  }
         		});

			}

			function chart4() {
                if ($('#chart_4').size() != 1) {
                    return;
                }
                //server load
                var options = {
                    series: {
                        shadowSize: 1,
					/*threshold: [{
						below: 50,
						color: '#1ac6ff'
					},{
						below: 20,
						color: '#99e6ff'
					}]*/
                    },
                    lines: {
                        show: true,
                        lineWidth: 0.5,
                        fill: true,
                        fillColor: {
                            colors: [{
                                opacity: 0.1
                            }, {
                                opacity: 1
                            }]
                        }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickColor: "#eee",
                        tickFormatter: function(v) {
                            return v + "%";
                        }
                    },
                    xaxis: {
                        show: false,
                    },
                    colors: ["#006080"],
                    grid: {
                        tickColor: "#eee",
                        borderWidth: 0
                    }
                };

				getMemoryData(0, options);
            }

			function chart5() {
                if ($('#chart_5').size() != 1) {
                    return;
                }
                //server load
                var options = {
                    series: {
                        shadowSize: 1
                    },
                    lines: {
                        show: true,
                        lineWidth: 0.5,
                        fill: true,
                        fillColor: {
                            colors: [{
                                opacity: 0.1
                            }, {
                                opacity: 1
                            }]
                        }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickColor: "#eee",
                        tickFormatter: function(v) {
                            return v + "%";
                        }
                    },
                    xaxis: {
                        show: false,
                    },
                    colors: ["#0099cc"],
                    grid: {
                        tickColor: "#eee",
                        borderWidth: 0,
						//markings: [{yaxis: { from: 0, to: 50 }, color: "#E8E8E8"}]

                    }
                };

				getCPUData(0, options);

            }

			
			chart4();
			chart5();

		},

        init: function() {

            this.handleTable();
            this.initKNOB();
            this.initCharts();
			this.initEasyPieCharts();
			this.initSparklineCharts();
			this.initDynCharts();

        }
    };

}();

if (App.isAngularJsApp() === false) {
    jQuery(document).ready(function() {
        Dashboard.init(); // init metronic core componets
    });
}
