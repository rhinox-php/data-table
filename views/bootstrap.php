<?php
/** @var \Rhino\DataTable\DataTable $dataTable */
if (!isset($dataTable)) {
    throw new \Exception('Expected $dataTable to be defined');
}
?>
<table id="<?= $dataTable->getId(); ?>" class="table table-striped table-bordered table-hover rhinox-data-table">
    <thead>
        <tr>
            <?php foreach ($dataTable->getColumns() as $column): ?>
                <th class="d-table-header"><?= $column->getHeader(); ?></th>
            <?php endforeach; ?>
        </tr>
        <tr class="rhinox-data-table-search-bar">
            <?php foreach ($dataTable->getColumns() as $columnId => $column): ?>
                <td class="rhinox-data-table-col-filter rhinox-data-table-search-<?= $column->getKey(); ?> <?= $columnId == 0 ? 'rhinox-data-table-col-filter-first' : ''; ?>" data-column="<?=$columnId;?>">
                    <?php if ($column->isSearchable()): ?>
                        <?php if ($column->hasFilterSelect()): ?>
                            <select class="custom-select" id="rhinox-data-table-filter-<?=strtr($column->getName(), '_', '-');?>">
                                <option value="">Any</option>
                                <?php foreach ($column->getFilterSelect() as $label => $query): ?>
                                    <option value="<?=$label;?>"><?=$label;?></option>
                                <?php endforeach;?>
                            </select>
                        <?php elseif ($column->getFilterDateRange()): ?>
                            <input class="form-control rhinox-data-table-date-range" type="text" placeholder="Filter <?= $column->getHeader(); ?>" id="rhinox-data-table-date-range-<?= $columnId ?>" name="rhinox-data-table-date-range-<?= $columnId ?>" />
                        <?php elseif ($column->getFilterNumeric()): ?>
                            <?php include __DIR__ . '/bootstrap/column-filter-numeric.php'; ?>
                        <?php else: ?>
                            <?php include __DIR__ . '/bootstrap/column-filter-text.php'; ?>
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
<?php
$columnDefs = [];
$searchCols = [];
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
    // Default column filter values
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
    'tableButtons' => $dataTable->getTableButtons(),
    'searchCols' => $searchCols,
    'hasSelect' => $this->hasSelect(),
];
?>
<script>
    window.rhinoxDataTables = window.rhinoxDataTables || [];
    window.rhinoxDataTables.push(<?= json_encode($jsonConfig, $dataTable->getDebug() ? JSON_PRETTY_PRINT : null); ?>);
</script>
