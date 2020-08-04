document.addEventListener('DOMContentLoaded', () => {
    let initialDataTables = [];
    if (window.rhinoxDataTables) {
        initialDataTables = window.rhinoxDataTables;
    }
    console.log('Initial data table configs', initialDataTables);

    $.fn.dataTable.ext.errMode = 'throw';
    $.fn.DataTable.ext.pager.numbers_length = 13;

    for (const dataTableConfig of initialDataTables) {
        initDataTable(dataTableConfig);
    }
});

const initDataTable = (dataTableConfig) => {
    console.log('Initializing data table', dataTableConfig);
    const selector = '#' + dataTableConfig.id;
    const table = $(selector)
        // .on('xhr.dt', function (e, settings, json, xhr) {
        //     if (json && json.footers) {
        //         for (let columnName in json.footers) {
        //             $('#<?=$id;?>-' + columnName).html(json.footers[columnName]);
        //         }
        //     }
        // })
        // .on('processing.dt', function (e, settings, processing) {
        //     if (processing) {
        //         $(selector).addClass('rhinox-data-table-loading');
        //     } else {
        //         $(selector).removeClass('rhinox-data-table-loading');
        //     }
        // })
        .DataTable({
            dom:
                "<'rhinox-data-table-header'<'row rhinox-data-table-top'<'col-sm-9 rhinox-data-table-left'B><'col-sm-3 rhinox-data-table-right'f<'rhinox-data-table-advanced'>>>>" +
                "<'rhinox-data-table-error'>" +
                "<'rhinox-data-table-scroll'tr>" +
                "<'rhinox-data-table-footer'<'row'<'col-sm-6'i><'col-sm-6'pl>>>",
            processing: true,
            serverSide: true,
            language: {
                buttons: {
                    colvis: 'Change columns',
                },
            },
            autoWidth: false,
            orderCellsTop: true,
            order: dataTableConfig.defaultOrder,
            ajax: {
                url: dataTableConfig.url,
                type: 'POST',
                data: function (d, callback) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                },
                error: function (xhr, error, thrown) {
                    console.log('Data tables ajax error', xhr, error, thrown, xhr.responseText);
                    $(selector)
                        .closest('.rhinox-data-table-wrapper')
                        .find('.rhinox-data-table-error')
                        .html('')
                        .append('<div class="alert alert-danger">Error processing table</div>')
                        .append(xhr.responseText);
                    $('.dataTables_processing').hide();
                },
            },
            columnDefs: dataTableConfig.columnDefs,
            lengthMenu: [[10, 25, 50, 100, 250, 500], [10, 25, 50, 100, 250, 500]],
            stateSave: true,
            stateSaveCallback: function (settings, data) {
                localStorage.setItem(dataTableConfig.id, JSON.stringify(data));
            },
            // stateLoadCallback: function (settings, data) {
            //     let data = null;
            //     // URL filters
            //     let filter = $.query.get('filter');
            //     for (let key in filter) {
            //         if (filter[key]) {
            //             $('body').addClass('rhinox-data-table-advanced-enabled');
            //             localStorage.setItem('advancedTableOptions', true)
            //             let data = {
            //                 time: new Date().getTime(),
            //                 columns: [],
            //             };
            //             for (let i = 0; i < columnDefs.length; i++) {
            //                 data.columns.push({
            //                     search: {
            //                         caseInsensitive: true,
            //                         regex: false,
            //                         smart: true,
            //                         search: filter[columnDefs[i].data] || null,
            //                     },
            //                 });
            //             }
            //             break;
            //         }
            //     }
            //     if (data === null) {
            //         // Saved state
            //         // <? php if ($this -> isRememberSettingsEnabled()): ?>
            //         //         data = JSON.parse(localStorage.getItem(dataTableConfig.id));
            //         // <? php endif; ?>
            //     }
            //     if (data && data.columns) {
            //         for (let i = 0; i < data.columns.length; i++) {
            //             // $('#<?=$this->getId();?> [data-column="' + i + '"] :input').val(data.columns[i].search.search);
            //         }
            //     }
            //     return data;
            // },
            buttons: [
                {
                    text: '<i class="fa fa-download"></i> Download CSV',
                    action: function (e, dt, node, config) {
                        let arrayToObject = function (mixed) {
                            if (Array.isArray(mixed)) {
                                let object = {};
                                for (let i = 0; i < mixed.length; i++) {
                                    object[i] = arrayToObject(mixed[i]);
                                }
                                return object;
                            }
                            if (typeof mixed === 'object') {
                                let object = {};
                                for (let key in mixed) {
                                    object[key] = arrayToObject(mixed[key]);
                                }
                                return object;
                            }
                            return mixed;
                        };
                        let params = arrayToObject(dt.ajax.params());
                        params.csv = true;
                        $.redirect(location.href, params, 'post');
                    }
                },
                'colvis',
                {
                    text: '<i class="fa fa-reload"></i> Reset Settings',
                    action: function (e, dt, node, config) {
                        localStorage.removeItem(dataTableConfig.id);
                        window.location.reload();
                    }
                },
                {
                    text: '<i class="fa fa-sync-alt"></i> Refresh Data',
                    action: function (e, dt, node, config) {
                        dt.draw();
                    },
                    className: 'rhinox-data-table-advanced-button',
                },
                {
                    text: 'Select All',
                    action: function (e, dt, node, config) {
                        selectAll();
                    },
                },
            ],
        });


    // Column searching
    let searchDebounce = null;

    $(selector).closest('.dataTables_wrapper').on('keyup change', '.rhinox-data-table-col-filter', function () {
        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }
        searchDebounce = setTimeout(function () {
            searchDebounce = null;
            let draw = false;
            $(selector).closest('.dataTables_wrapper').find('.rhinox-data-table-col-filter').each(function () {
                let value = $(this).find(':input').val();
                let column = table.column($(this).data('column'));
                if (column.search() !== value) {
                    column.search(value);
                    draw = true;
                }
            });
            if (draw) {
                table.draw();
            }
        }.bind(this), 500);
    });

    // Select rows/checkbox
    let lastChecked = false;
    $(selector).on('click', ':checkbox', function (e) {
        e.preventDefault();
    });
    $(selector).on('mousedown', 'td', function (e) {
        let checkbox = $(this).find(':checkbox');
        if (checkbox.length) {
            let rowIndex = $(this).closest('tr').index();
            let checked = !checkbox.is(':checked');
            if (e.shiftKey) {
                e.preventDefault();
                let index = $(this).index();
                let tableBody = $(this).closest('tbody');
                let minChecked = Math.min(rowIndex, lastChecked);
                let maxChecked = Math.max(rowIndex, lastChecked);
                tableBody.find('tr').each(function (i) {
                    if (i >= minChecked && i <= maxChecked) {
                        let cell = $(this).find('td').eq(index);
                        cell.find(':checkbox').prop('checked', checked);
                    }
                });
            }
            checkbox.prop('checked', checked);
            lastChecked = rowIndex;
        }
        checkbox.trigger('change');
    });

    let selectAllState = true;
    let selectAll = function () {
        $(selector).find(':checkbox').prop('checked', selectAllState).trigger('change');
        selectAllState = !selectAllState;
    };

    $(selector).find('.rhinox-data-table-date-range').each(function() {
        console.log(this);
        if (!window.moment) {
            console.error('Date range filters require moment.js');
            return;
        }
        if (!$.fn.daterangepicker) {
            console.error('Date range filters require jQuery Date Range Picker');
            return;
        }
        $(this).daterangepicker({
            autoUpdateInput: false,
            timePicker: true,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD HH:mm',
            },
            ranges: {
                'Today': [moment().startOf('day'), moment().endOf('day')],
                'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                'Last 30 Days': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                'This Month': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                'Last Month': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')],
            },
        });

        $(this).on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' to ' + picker.endDate.format('YYYY-MM-DD HH:mm')).trigger('change');
        });

        $(this).on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('').trigger('change');
        });
    });
};
