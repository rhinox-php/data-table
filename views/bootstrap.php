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
                        <i>Filters:</i>
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
                "<'rx-data-table-table'tr>" +
                "<'rx-data-table-nav'<'rx-data-table-count'i><'rx-data-table-pagination'p>>",
            classes: {
                sFilterInput:  "form-control",
                sLengthSelect: "form-control",
            },
            ajax: {
                url: '',
                type: 'post',
            },
            buttons: [
                {
                    text: '<i class="fa fa-download"></i> Download CSV',
                    action: function (e, dt, node, config) {
                        $.redirect(window.location.pathname, $.extend(dt.ajax.params(), {
                            csv: true,
                        }), 'post');
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

        $('#<?= $this->getId(); ?>').closest('.dataTables_wrapper').on('keyup change', '.rx-datatable-col-filter', function() {
            var value = $(this).find(':input').val();
            var column = table.column($(this).data('column'));
            if (column.search() !== value) {
                column.search(value);
                table.draw();
            }
        });
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
    }
    
    .rx-datatable-wrapper .rx-datatable {
        width: 100% !important;
    }
    
    .rx-datatable-wrapper .dataTables_wrapper {
        overflow: auto;
    }
    
    .rx-datatable-wrapper .rx-data-table-page-size {
        width: 50%;
        float: left;
    }
    
    .rx-datatable-wrapper .rx-data-table-search {
        width: 50%;
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
