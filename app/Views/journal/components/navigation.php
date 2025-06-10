<?php if ($previous) { ?>
    <a class='pull-left' href="<?= base_url(); ?>journal/view/<?= hash_id($previous); ?>" title='Previous Month'>
        <i class='fa fa-minus-circle' style='font-size:20pt;'></i>
    </a>
<?php } ?>

<?php if ($next) { ?>
    <a class='pull-right' href="<?= base_url(); ?>vournal/view/<?= hash_id($next); ?>" title='Next Month'>
        <i class='fa fa-plus-circle' style='font-size:20pt;'></i></a>
<?php } ?>