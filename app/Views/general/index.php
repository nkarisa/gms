<?= $this->extend('layouts/default') ?>

<?= $this->section('dir') ?>
<?=view_cell('App\Cells\Meta::show', []);?>
<?= $this->endSection() ?>

<?= $this->section('page_title') ?>
<?=view_cell('App\Cells\Title::show', []);?>
<?= $this->endSection() ?>

<?= $this->section('navigation') ?>
<?=view_cell('App\Cells\NavBar::show', []);?>
<?= $this->endSection() ?>

<?= $this->section('header') ?>
<?=view_cell('App\Cells\Header::show', []);?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<?=view_cell('App\Cells\BreadCrumb::show', []);?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <?php $session = session();?>
    <?=view_cell('App\Cells\Content::show', ['output' => $output]);?>
<?= $this->endSection() ?>

<?= $this->section('footer') ?>
<?=view_cell('App\Cells\Footer::show', []);?>
<?= $this->endSection() ?>

