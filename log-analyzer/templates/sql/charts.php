

<h1>Charts</h1>

<?php include(__DIR__ . '/_view-header.php'); ?>

<div class="chart-summary"></div>

<br>

<table class="std-grid">
    <tr>
    <?php foreach ($this->summary[0] as $field => $val) { ?>
        <th><?= $field; ?></th>
    <?php } ?>
    </tr>
    <?php foreach ($this->summary as $row) { ?>
        <tr>
            <?php foreach ($row as $field => $val) { ?>
                <td><?= $val; ?></td>
            <?php } ?>
        </tr>
    <?php } ?>
</table>

<script type="text/javascript">
$(function(){

    StdCharts.drawTableColumns('.chart-summary', <?= json_encode($this->summary); ?>, {
        dateAsString: true,
        height: 600
    });
});
</script>