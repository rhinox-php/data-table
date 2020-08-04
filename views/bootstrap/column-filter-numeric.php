<div class="input-group">
    <div class="input-group-prepend">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
        <div class="dropdown-menu">
            <div class="dropdown-item">Like</div>
            <div class="dropdown-item">Greater than</div>
            <div class="dropdown-item">Less than</div>
            <div class="dropdown-item">Between</div>
            <div class="dropdown-item">Equals</div>
        </div>
    </div>
    <input type="text" class="form-control rhinox-data-table-column-filter" placeholder="Filter <?= $column->getHeader(); ?>" />
</div>
