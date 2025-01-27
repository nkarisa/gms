
<style>
thead tr td {
    font-weight:bold;
}
</style>    

<table class="table table-striped">
    <thead>
        <tr>
            <td><?=get_phrase('transfer_request_date');?></td>
            <td><?=get_phrase('transfer_approved_date');?></td>
            <td><?=get_phrase('voucher_date');?></td>
            <td><?=get_phrase('voucher_number');?></td>
            <td><?=get_phrase('source_account');?></td>
            <td><?=get_phrase('destination_account');?></td>
            <td><?=get_phrase('transfer_amount');?></td>
            <td><?=get_phrase('view_transfer');?></td>
        </tr>
    </thead>
    <tbody>
        <?php foreach($funds_transfers as $funds_transfer){
            extract($funds_transfer);    
        ?>
            <tr>
                <td><?=$funds_transfer_created_date;?></td>
                <td><?=$voucher_created_date;?></td>
                <td><?=$voucher_date;?></td>
                <td><a target='__blank' href='<?=base_url();?>voucher/view/<?=hash_id($voucher_id,'encode');?>'><?=$voucher_number;?></a></td>
                <td><?=$funds_transfer_source_account_id;?></td>
                <td><?=$funds_transfer_target_account_id;?></td>
                <td><?=number_format($funds_transfer_amount,2);?></td>
                <td><a target='__blank' href='<?=base_url();?>funds_transfer/view/<?=hash_id($funds_transfer_id,'encode');?>'><?=get_phrase('view_transfer');?></a></td>
            </tr>
        <?php }?>
    </tbody>
</table>