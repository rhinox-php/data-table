<?php $id = uniqid('table-'); ?>
<table id="<?= $id; ?>" class="table table-striped table-bordered table-hover">
    <thead>
    <tr>
        <?php foreach ($this->getColumns() as $column): ?>
            <th class="d-table-header"><?= $column->getLabel(); ?></th>
        <?php endforeach; ?>
    </tr>
    <tr>
        <?php foreach ($this->getColumns() as $i => $column): ?>
            <?php if ($i === 0): ?>
                <td class="rx-datatable-col-filter-first">
                    <i>Filters:</i>
                </td>
            <?php else: ?>
                <td class="rx-datatable-col-filter">
                    <input class="form-control" />
                </td>
            <?php endif; ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    </tbody>
</table>
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
        var table = $('#<?= $id; ?>').DataTable({
            dom:
                "<'row'<'col-sm-6'lB><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
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
            scrollX: true,
            processing: true,
            serverSide: true,
            columnDefs: <?= json_encode($columnDefs); ?>,
            order: [[ 1, 'desc' ]],
        });

        table.columns().every(function() {
            var column = this;
            $('#<?= $id; ?>').closest('.dataTables_scroll').find('.rx-datatable-col-filter').eq(column.index()).on('keyup change', function() {
                var value = $(this).find('input').val();
                if (column.search() !== value) {
                    column.search(value);
                    table.draw();
                }
            });
        });
    })(jQuery);
</script>

<style>
    .table > thead > tr .rx-datatable-col-filter {
        padding: 0;
        margin: 0;
    }
    
    .table > thead > tr .rx-datatable-col-filter .form-control {
        border: 0;
        width: 100%;
    }
</style>
