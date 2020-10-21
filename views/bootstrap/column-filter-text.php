<div class="input-group">
    <div class="input-group-prepend">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
        <div class="dropdown-menu">
            <div class="dropdown-item rhinox-data-table-col-filter-type" data-filter="">Like</div>
            <div class="dropdown-item rhinox-data-table-col-filter-type" data-filter="=">Equals</div>
        </div>
    </div>
    <input type="text" class="form-control rhinox-data-table-column-filter" placeholder="Filter <?= $column->getHeader(); ?>" value="<?= $column->getDefaultColumnFilter(); ?>" />
    <input type="hidden" />
</div>
