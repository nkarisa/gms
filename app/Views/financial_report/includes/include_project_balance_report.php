<?php
//print_r($projects_balance_report['body']);
?>
<table class="table table-striped" id="project_balance_table">
    <thead>
        <tr>
            <?php foreach ($projects_balance_report['headers'] as $header) { ?>
                <th><?= $header; ?></th>
            <?php } ?>

        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($projects_balance_report['body'] as $project) {
        ?>
            <tr>
                <?php foreach ($projects_balance_report['headers'] as $header_key => $header) { ?>
                    <td><?= is_numeric($project[$header_key]) ? number_format($project[$header_key], 2) : $project[$header_key]; ?></td>
                <?php } ?>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>