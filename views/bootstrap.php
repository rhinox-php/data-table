<div class="rx-datatable-wrapper">
    <table id="<?= $this->getId(); ?>" class="table table-striped table-bordered table-hover rx-datatable">
        <thead>
        <tr>
            <?php foreach ($this->getColumns() as $column): ?>
                <th class="d-table-header"><?= $column->getLabel(); ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($this->getColumns() as $i => $column): ?>
                <?php if ($i === 0): ?>
                    <td class="rx-datatable-col-filter rx-datatable-col-filter-first">
                    </td>
                <?php else: ?>
                    <td class="rx-datatable-col-filter" data-column="<?= $i; ?>">
                        <input class="form-control" value="<?= $column->getDefaultColumnFilter(); ?>" />
                    </td>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php
$columnDefs = [];
foreach ($this->getColumns() as $i => $column) {
    $columnDefs[] = [
        'targets' => $i,
        'searchable' => $i > 0,
        'orderable' => $i > 0,
        'data' => $column->getKey(),
        'visible' => $column->isVisible(),
    ];
    $searchCols[] = $column->getDefaultColumnFilter() ? [
        'search' => $column->getDefaultColumnFilter(),
    ] : null;
}
?>
<script>
    (function($) {
        if (typeof RhinoDataTables === 'undefined') {
            RhinoDataTables = {};
        }

        $.fn.dataTableExt.sErrMode = 'throw';
        /*
        $.fn.dataTable.ext.errMode = function(settings, techNote, message) {
            console.error(settings);
            console.error(techNote);
            console.error(message);
        };
        */

        var table = $('#<?= $this->getId(); ?>').DataTable({
            dom:
                "<'rx-data-table-controls'<'rx-data-table-page-size'lB><'rx-data-table-search'f>>" +
                "<'rx-datatable-error'>" +
                "<'rx-data-table-table'tr>" +
                "<'rx-data-table-nav'<'rx-data-table-count'i><'rx-data-table-pagination'p>>",
            classes: {
                sFilterInput:  "form-control",
                sLengthSelect: "form-control",
            },
            ajax: {
                url: '',
                type: 'post',
                error: (xhr, error, thrown) => {
                    $('#<?= $this->getId(); ?>')
                        .closest('.rx-datatable-wrapper')
                        .find('.rx-datatable-error')
                        .html('')
                        .append('<div class="alert alert-danger">Error processing table <a href="#" onclick="$(\'.rx-datatable-error-details\').show()">(show)</div></div>')
                        .append('<div style="display: none" class="rx-datatable-error-details">' + xhr.responseText + '</div>');
                    $('.dataTables_processing').hide();
                },
            },
            lengthMenu: [[10, 25, 50, 100, 200, 500], [10, 25, 50, 100, 200, 500]],
            buttons: [
                {
                    text: '<i class="fa fa-download"></i> Download CSV',
                    action: function (e, dt, node, config) {
                        var arrayToObject = function(mixed) {
                            if (Array.isArray(mixed)) {
                                var object = {};
                                for (var i = 0; i < mixed.length; i++) {
                                    object[i] = arrayToObject(mixed[i]);
                                }
                                return object;
                            }
                            if (typeof mixed === 'object') {
                                var object = {};
                                for (var key in mixed) {
                                    object[key] = arrayToObject(mixed[key]);
                                }
                                return object;
                            }
                            return mixed;
                        };
                        var params = arrayToObject(dt.ajax.params());
                        params.csv = true;
                        $.redirect(location.href, params, 'post');
                    }
                },
                'colvis',
                {
                    text: '<i class="fa fa-reload"></i> Reset',
                    action: function (e, dt, node, config) {
                        localStorage.removeItem(<?= json_encode($this->getId()); ?>);
                        window.location.reload();
                    }
                },
                <?php foreach ($this->getTableButtons() as $button): ?>
                <?php
                    $className = $button['class'] ?: '';
                    if ($button['name']) {
                        $className .= ' rx-button-' . $button['name'];
                    }
                ?>
                {
                    text: <?= json_encode($button['text']); ?>,
                    action: function (e, dt, node, config) {
                        <?php if ($button['confirm']): ?>
                            if (!confirm(<?= json_encode($button['confirm']); ?>)) {
                                return;
                            }
                        <?php endif; ?>
                        <?php if ($button['type'] === 'selectAll'): ?>
                            selectAll();
                        <?php elseif ($button['href']): ?>
                            $.redirect(<?= json_encode($button['href']); ?>, {}, 'get');
                        <?php else: ?>
                            if (RhinoDataTables['<?= $this->getId(); ?>'].buttons[<?= json_encode($button['name']); ?>]) {
                                RhinoDataTables['<?= $this->getId(); ?>'].buttons[<?= json_encode($button['name']); ?>].click();
                            }
                        <?php endif; ?>
                    },
                    className: <?= json_encode($className); ?>,
                },
                <?php endforeach; ?>
            ],
            language: {
                buttons: {
                    colvis: 'Change columns',
                },
            },
            orderCellsTop: true,
//            scrollX: true,
            processing: true,
            serverSide: true,
            stateSave: <?= json_encode($this->getSaveState()); ?>,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem(<?= json_encode($this->getId()); ?>, JSON.stringify(data));
            },
            stateLoadCallback: function(settings, data) {
                var data = JSON.parse(localStorage.getItem(<?= json_encode($this->getId()); ?>));
                if (data && data.columns) {
                    for (var i = 0; i < data.columns.length; i++) {
                        $('#<?= $this->getId(); ?> [data-column="' + i + '"] :input').val(data.columns[i].search.search);
                    }
                }
                return data;
            },
            columnDefs: <?= json_encode($columnDefs, JSON_PRETTY_PRINT); ?>,
            order: <?= json_encode($this->getDefaultOrder()); ?>,
            searchCols: <?= json_encode($searchCols); ?>,
        });

        var searchDebounce = null;

        $('#<?= $this->getId(); ?>').closest('.dataTables_wrapper').on('keyup change', '.rx-datatable-col-filter', function() {
            if (searchDebounce) {
                clearTimeout(searchDebounce);
            }
            searchDebounce = setTimeout(function() {
                searchDebounce = null;
                var value = $(this).find(':input').val();
                var column = table.column($(this).data('column'));
                if (column.search() !== value) {
                    column.search(value);
                    table.draw();
                }
            }.bind(this), 500);
        });

        var lastChecked = false;
        $('#<?= $this->getId(); ?>').on('click', ':checkbox', function(e) {
            e.preventDefault();
        });
        $('#<?= $this->getId(); ?>').on('mousedown', 'td', function(e) {
            var checkbox = $(this).find(':checkbox');
            if (checkbox.length) {
                var rowIndex = $(this).closest('tr').index();
                var checked = !checkbox.is(':checked');
                if(e.shiftKey) {
                    e.preventDefault();
                    var index = $(this).index();
                    var tableBody = $(this).closest('tbody');
                    var minChecked = Math.min(rowIndex, lastChecked);
                    var maxChecked = Math.max(rowIndex, lastChecked);
                    tableBody.find('tr').each(function(i) {
                        if (i >= minChecked && i <= maxChecked) {
                            var cell = $(this).find('td').eq(index);
                            cell.find(':checkbox').prop('checked', checked);
                        }
                    });
                }
                checkbox.prop('checked', checked);
                lastChecked = rowIndex;
                if (RhinoDataTables['<?= $this->getId(); ?>'].selectHanlder) {
                    RhinoDataTables['<?= $this->getId(); ?>'].selectHanlder(RhinoDataTables['<?= $this->getId(); ?>'].getSelected());
                }
            }
            checkbox.trigger('change');
        });

        var selectAllState = true;
        var selectAll = function() {
            $('#<?= $this->getId(); ?> :checkbox').prop('checked', selectAllState);
            if (RhinoDataTables['<?= $this->getId(); ?>'].selectHanlder) {
                RhinoDataTables['<?= $this->getId(); ?>'].selectHanlder(RhinoDataTables['<?= $this->getId(); ?>'].getSelected());
            }
            selectAllState = !selectAllState;
        };

        RhinoDataTables['<?= $this->getId(); ?>'] = {
            table: table,
            selectAll: selectAll,
            buttons: {},
            selectHanlder: null,
            select: function(callback) {
                RhinoDataTables['<?= $this->getId(); ?>'].selectHanlder = callback;
            },
            getSelected: function() {
                var result = [];
                $('#<?= $this->getId(); ?> :checked').each(function() {
                    result.push(table.row($(this).closest('tr')).data());
                });
                return result;
            },
            getButton: function(name) {
                return {
                    click: function(callback) {
                        if (!RhinoDataTables['<?= $this->getId(); ?>'].buttons[name]) {
                            RhinoDataTables['<?= $this->getId(); ?>'].buttons[name] = {};
                        }
                        RhinoDataTables['<?= $this->getId(); ?>'].buttons[name].click = callback;
                    },
                };
            },
        };
    })(jQuery);
</script>

<style>
    .rx-datatable-wrapper .table > thead > tr .rx-datatable-col-filter {
        padding: 0;
        margin: 0;
    }

    .rx-datatable-wrapper .table > thead > tr .rx-datatable-col-filter .form-control {
        border: 0;
        width: 100%;
    }

    .rx-datatable-wrapper .table > thead > tr .rx-datatable-col-filter-first {
        padding: 8px;
        white-space: nowrap;
    }

    .rx-datatable-wrapper .rx-datatable {
        width: 100% !important;
    }

    .rx-datatable-wrapper .dataTables_wrapper {
        overflow: auto;
    }

    .rx-datatable-wrapper .rx-data-table-page-size {
        width: 70%;
        float: left;
    }

    .rx-datatable-wrapper .rx-data-table-search {
        width: 30%;
        float: right;
    }

    .rx-datatable-wrapper .rx-data-table-count {
        width: 50%;
        float: left;
    }

    .rx-datatable-wrapper .rx-data-table-pagination {
        width: 50%;
        float: right;
    }

    .rx-datatable-wrapper .dataTables_length {
        float: left;
        margin-right: 10px;
    }
</style>
