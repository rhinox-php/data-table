<?php
/** @var \Rhino\DataTable\DataTable $dataTable */
if (!isset($dataTable)) {
    throw new \Exception('Expected $dataTable to be defined');
}
?>
<div class="rhinox-data-table-wrapper">
    <table id="<?= $dataTable->getId(); ?>" class="table table-striped table-bordered table-hover rhinox-data-table">
        <thead>
        <tr>
            <?php foreach ($dataTable->getColumns() as $column): ?>
                <th class="d-table-header"><?= $column->getHeader(); ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($dataTable->getColumns() as $columnId => $column): ?>
                <td class="rhinox-data-table-col-filter rhinox-data-table-search-<?= $column->getKey(); ?> <?= $columnId == 0 ? 'rhinox-data-table-col-filter-first' : ''; ?>" data-column="<?=$columnId;?>">
                    <?php if ($column->isSearchable()): ?>
                        <?php if ($column->hasFilterSelect()): ?>
                            <select class="form-control" id="rhinox-data-table-filter-<?=strtr($column->getName(), '_', '-');?>">
                                <option value="">Any</option>
                                <?php foreach ($column->getFilterSelect() as $label => $query): ?>
                                    <option value="<?=$label;?>"><?=$label;?></option>
                                <?php endforeach;?>
                            </select>
                        <?php elseif ($column->hasFilterDateRange()): ?>
                            <input class="form-control rhinox-data-table-column-filter-date-range" type="text" placeholder="Filter <?= $column->getHeader(); ?>" id="rhinox-data-table-date-range-<?= $columnId ?>" name="rhinox-data-table-date-range-<?= $columnId ?>" />
                        <?php else: ?>
                            <input class="form-control rhinox-data-table-column-filter" placeholder="Filter <?= $column->getHeader(); ?>" />
                        <?php endif;?>
                    <?php else: ?>
                        <input class="form-control" type="hidden" />
                    <?php endif;?>
                </td>
            <?php endforeach;?>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php
$columnDefs = [];
$i = 0;
foreach ($dataTable->getColumns() as $column) {
    $columnDefs[] = [
        'targets' => $i++,
        'data' => $column->getKey(),
        'searchable' => $column->isSearchable(),
        'orderable' => $column->isSortable(),
        'visible' => $column->isVisible(),
        'className' => $column->getClassName(),
    ];
    $searchCols[] = $column->getDefaultColumnFilter() ? [
        'search' => $column->getDefaultColumnFilter(),
    ] : null;
}
$jsonConfig = [
    'id' => $dataTable->getId(),
    'url' => $dataTable->getUrl(),
    'columnDefs' => $columnDefs,
    'defaultOrder' => $dataTable->getDefaultOrder(),
    'saveState' => $dataTable->getSaveState(),
];
?>
<script>
    window.rhinoxDataTables = window.rhinoxDataTables || [];
    window.rhinoxDataTables.push(<?= json_encode($jsonConfig); ?>);
</script>

<style>
    .rhinox-data-table-wrapper .table > thead > tr .rhinox-data-table-col-filter {
        padding: 0;
        margin: 0;
    }

    .rhinox-data-table-wrapper .table > thead > tr .rhinox-data-table-col-filter .form-control {
        border: 0;
        width: 100%;
    }

    .rhinox-data-table-wrapper .table > thead > tr .rhinox-data-table-col-filter-first {
        padding: 8px;
        white-space: nowrap;
    }

    .rhinox-data-table-wrapper .rhinox-data-table {
        width: 100% !important;
    }

    .rhinox-data-table-wrapper .dataTables_wrapper {
        overflow: auto;
    }

    .rhinox-data-table-wrapper .rhinox-data-table-page-size {
        width: 70%;
        float: left;
    }

    .rhinox-data-table-wrapper .rhinox-data-table-search {
        width: 30%;
        float: right;
    }

    .rhinox-data-table-wrapper .rhinox-data-table-count {
        width: 50%;
        float: left;
    }

    .rhinox-data-table-wrapper .rhinox-data-table-pagination {
        width: 50%;
        float: right;
    }

    .rhinox-data-table-wrapper .dataTables_length {
        float: left;
        margin-right: 10px;
    }
</style>
