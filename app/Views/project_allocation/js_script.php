<?php 
extract($result);
?>
<script>
    $("#project_allocation_extended_end_date").datepicker({
        format: 'yyyy-mm-dd',
        //startDate: '<?php //=$project_end_date;?>//'
        startDate: '<?= $result['project_end_date'] ?? '' ?>'
    })
</script>