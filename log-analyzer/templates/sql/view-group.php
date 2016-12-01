
<style type="text/css">
    nav{
        margin: 1em 0;
    }
    a.active{
        font-weight: bold;
        border-bottom: solid 2px blue;
    }
    td.sql{
        white-space: pre-wrap;
        font-family: monospace;
        word-break: break-all;
    }
    table tbody td.date{
        width: 100px;
        padding: 2px 5px;
    }
</style>

<h1>View Sql-Log Details</h1>

<?php include(__DIR__ . '/_view-header.php'); ?>

<?php if ($this->data) { ?>

    <table class="table table-bordered">
        <tr>
            <th></th>
            <th>Count</th>
            <th>SQL</th>
            <th>First Date</th>
            <th>Last Date</th>
        </tr>
        <?php foreach ($this->data as $index => $row) { ?>
            <?php
            $viewUrl = href("sql/view-query/{$this->session['id']}?type=$this->page&query=" . urlencode($row['sql']));
            ?>
            <tr>
                <td><?= $index + 1; ?></td>
                <td><?= $row['cnt']; ?></td>
                <td class="sql"><?= $row['sql']; ?></td>
                <td class="date"><?= $row['min_date']; ?></td>
                <td class="date"><?= $row['max_date']; ?></td>
                <td><a href="<?= $viewUrl; ?>">view</a></td>
            </tr>
        <?php } ?>
    </table>

<?php } else { ?>
    <p>No Data</p>
<?php } ?>

