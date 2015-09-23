<?php $id = uniqid('table-'); ?>
<table id="<?= $id; ?>" class="table table-striped table-bordered table-hover">
    <thead>
        <?php foreach ($this->getColumns() as $column): ?>
            <th><?= $column->getLabel(); ?></th>
        <?php endforeach; ?>
    </thead>
    <tbody>
    </tbody>
</table>
<script>
    lazyLoad('data-tables', function($) {
        $('#<?= $id; ?>').DataTable({
            ajax: '',
            processing: true,
            serverSide: true,
        });
    });
</script>
