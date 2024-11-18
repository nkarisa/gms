<style>
    @media print {
    .no-print {
        display:none;
    }
    }
</style>

<div class='row'>
    <div class='col-xs-12'>
        <div onclick="PrintElem('#voucher_print')" class="btn btn-default"><?= get_phrase('print'); ?></div>
        <a class = "btn btn-default" href = "<?=base_url();?>journal/view/<?=$journal_id;?>"><?=get_phrase('back_to_journal');?></a>
    </div>
</div>
<hr />
<div id="voucher_print">
    <?php
    foreach ($vouchers as $voucher) {
        extract($voucher);
    ?>
        <div class='row'>
            <div class="col-xs-12">
                <div class="panel panel-default" data-collapsed="0">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="entypo-plus-circled"></i>
                            <?php echo get_phrase('transaction_voucher'); ?>
                        </div>
                    </div>

                    <div class="panel-body" style="padding-left: 60px;padding-right: 30px;">
                        <?php 
                            include "common_view.php";
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="display" id = "break_page" style='page-break-after:always'></div>
    <?php

    }
    ?>
</div>

<script>
    function PrintElem(elem)
    {
        $(elem + ", #break_page").printThis({ 
		    debug: false,              
		    importCSS: true,             
		    importStyle: true,         
		    printContainer: false,       
		    loadCSS: "", 
		    pageTitle: "<?php echo get_phrase('payment_vouchers');?>",             
		    removeInline: false,        
		    printDelay: 333,            
		    header: null,             
		    formValues: true,         
		});
    }
</script>