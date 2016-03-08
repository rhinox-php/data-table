<?php $id = uniqid('table-'); ?>
<div class="rx-datatable-wrapper">
    <table id="<?= $id; ?>" class="table table-striped table-bordered table-hover rx-datatable">
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
                        <input class="form-control" />
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
        'visible' => $column->isVisible(),
    ];
}
?>
<script>
    (function($) {
        $.fn.dataTableExt.sErrMode = 'throw';
        
        var table = $('#<?= $id; ?>').DataTable({
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
            columnDefs: <?= json_encode($columnDefs); ?>,
            order: [[ 1, 'desc' ]],
        });

        $('#<?= $id; ?>').closest('.dataTables_wrapper').on('keyup change', '.rx-datatable-col-filter', function() {
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
