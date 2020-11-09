document.addEventListener('DOMContentLoaded', () => {
    let initialDataTables = [];
    if (window.rhinoxDataTables) {
        initialDataTables = window.rhinoxDataTables;
    }
    console.log('Initial data table configs', initialDataTables);

    $.fn.dataTable.ext.errMode = 'throw';
    $.fn.DataTable.ext.pager.numbers_length = 13;

    $.extend(true, $.fn.DataTable.ext.classes, {
        sWrapper: 'rhinox-data-table-wrapper dataTables_wrapper dt-bootstrap4',
        sFilterInput: 'form-control',
        sLengthSelect: 'custom-select form-control',
        sProcessing: 'dataTables_processing card',
        sPageButton: 'paginate_button page-item',
        sInfo: 'rhinox-data-table-info btn',
        sPaging: 'rhinox-data-table-paginate ',
        sLength: 'rhinox-data-table-length',
    });

    $.extend(true, $.fn.DataTable.Buttons.defaults, {
        dom: {
            container: {
                className: 'dt-buttons rhinox-data-table-buttons',
            },
            button: {
                className: 'btn rhinox-data-table-button',
            },
        },
    });

    for (const dataTableConfig of initialDataTables) {
        initDataTable(dataTableConfig);
    }
});

const initDataTable = (dataTableConfig) => {
    console.log('Initializing data table', dataTableConfig);
    const selector = '#' + dataTableConfig.id;

    const buttons = [];
    buttons.push(...dataTableConfig.tableButtons.map((tableButton) => {
        return {
            text: tableButton.text,
            method: tableButton.method,
            data: tableButton.data,
            url: tableButton.url,
            className: tableButton.class,
            action: (e, dt, node, config) => {
                if (tableButton.confirm && !confirm(tableButton.confirm)) {
                    return;
                }
                if (tableButton.href) {
                    window.location = tableButton.href;
                }
                // @todo support submit buttons
            },
        };
    }));
    buttons.push({
        text: '<i class="fa fa-download"></i> Download CSV',
        className: 'btn-secondary',
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
        },
    });
    buttons.push((dt, conf) => {
        return {
            extend: 'collection',
            text: function (dt) {
                return dt.i18n('buttons.colvis', 'Column visibility');
            },
            className: 'btn-secondary buttons-colvis rhinox-data-table-advanced-button',
            buttons: [{
                extend: 'columnsToggle',
                columns: conf.columns,
                columnText: conf.columnText,
            }],
        };
    });
    // buttons.push({
    //     text: '<i class="fa fa-reload"></i> Reset settings',
    //     className: 'btn-secondary rhinox-data-table-advanced-button',
    //     action: function (e, dt, node, config) {
    //         localStorage.removeItem(dataTableConfig.id);
    //         window.location.reload();
    //     }
    // });
    buttons.push({
        text: '<i class="fa fa-sync-alt"></i> Refresh data',
        className: 'btn-secondary rhinox-data-table-advanced-button',
        action: function (e, dt, node, config) {
            dt.draw();
        },
    });
    if (dataTableConfig.hasSelect) {
        buttons.push({
            text: 'Select all',
            className: 'btn-secondary',
            action: function (e, dt, node, config) {
                selectAll();
            },
        });
    }

    const table = $(selector)
        .DataTable({
            dom: `
                <'rhinox-data-table-header container-fluid'<'row rhinox-data-table-top'<'col-md-9 rhinox-data-table-left'B><'col-md-3 rhinox-data-table-right'f<'rhinox-data-table-advanced btn'>>>>
                <'rhinox-data-table-error'>
                "<'rhinox-data-table-scroll'tr>
                <'rhinox-data-table-footer container-fluid'ilp>
            `,
            processing: true,
            serverSide: true,
            language: {
                buttons: {
                    colvis: 'Change columns',
                },
                sSearch: '',
                sSearchPlaceholder: 'Search',
            },
            autoWidth: false,
            orderCellsTop: true,
            order: dataTableConfig.defaultOrder || undefined,
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
            searchCols: dataTableConfig.searchCols,
            stateSave: true,
            // stateSaveCallback: function (settings, data) {
            //     localStorage.setItem(dataTableConfig.id, JSON.stringify(data));
            // },
            stateLoadCallback: function (settings, stateData) {
                const data = {
                    time: new Date().getTime(),
                    columns: [],
                };
                // URL filters
                const queryString = new URLSearchParams(location.search);
                for (const columnDef of dataTableConfig.columnDefs) {
                    const filter = queryString.get(`filter[${columnDef.data}]`);
                    data.columns.push({
                        search: {
                            caseInsensitive: true,
                            regex: false,
                            smart: true,
                            search: filter,
                        },
                    });
                }
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
            // @todo provide alternate icon sets
                for (let i = 0; i < data.columns.length; i++) {
                    $(selector).find(`[data-column="${i}"] :input`).val(data.columns[i].search.search);
                }
                return data;
            },
            buttons,
        });

    $(selector).on('xhr.dt', (e, settings, json, xhr, bb) => {
        // Handle footer data
        let footer = $(selector).find('> tfoot');

        // Create footer if it doesn't exist
        if (!footer.length) {
            footer = $('<tfoot>').appendTo(selector);
        }

        // Clear the old footers
        footer.html('');

        if (!json.footerRows) {
            // Hide the footer if the server did not send footer data
            footer.hide();
        } else {
            for (const footerRow of json.footerRows) {
                if (!footerRow) {
                    continue;
                }
                const row = $('<tr>');
                let columnIndex = 0;
                for (const footerColumn of footerRow) {
                    $('<td>')
                        .addClass(dataTableConfig.columnDefs[columnIndex].className)
                        .html(footerColumn)
                        .appendTo(row);
                    columnIndex++;
                }
                footer.append(row);
            }
        }
    });

    $(selector).on('processing.dt', function (e, settings, processing) {
        if (processing) {
            $(selector).closest('.rhinox-data-table-wrapper').addClass('rhinox-data-table-processing');
        } else {
            $(selector).closest('.rhinox-data-table-wrapper').removeClass('rhinox-data-table-processing');
        }
    });

    // Column searching
    let searchDebounce = null;

    const searchColumns = () => {
        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }
        searchDebounce = setTimeout(function () {
            searchDebounce = null;
            let draw = false;
            $(selector).closest('.rhinox-data-table-wrapper').find('.rhinox-data-table-col-filter').each(function () {
                let prefix = $(this).find(':input[type=hidden]').val() || '';
                let value = prefix + $(this).find(':input:not(button)').val();
                let column = table.column($(this).data('column'));
                if (column.search() !== value) {
                    column.search(value);
                    draw = true;
                }
            });
            if (draw) {
                table.draw();
            }
        }, 500);
    };

    $(selector).closest('.rhinox-data-table-wrapper').on('keyup change', '.rhinox-data-table-col-filter', function () {
        searchColumns();
    });

    $(selector).closest('.rhinox-data-table-wrapper').on('click', '.rhinox-data-table-col-filter-type', function () {
        $(this).closest('.input-group-prepend').find('.btn').text($(this).data('filter'));
        $(this).closest('.input-group').find('input[type=hidden]').val($(this).data('filter'));
        searchColumns();
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

    $(selector).find('.rhinox-data-table-date-range').each(function () {
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

        $(this).on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' to ' + picker.endDate.format('YYYY-MM-DD HH:mm')).trigger('change');
        });

        $(this).on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('').trigger('change');
        });
    });


    $('.rhinox-data-table-advanced')
        .html('<i class="fa fa-cog"></i>')
        .click(function () {
            $('body').toggleClass('rhinox-data-table-advanced-enabled');
            localStorage.setItem('advancedTableOptions', $('body').is('.rhinox-data-table-advanced-enabled'));
        });

    if (localStorage.getItem('advancedTableOptions') == 'true') {
        $('body').addClass('rhinox-data-table-advanced-enabled');
    }
};
