<?php

use App\Libraries\System\Widgets\WidgetBase;
use App\Libraries\Core\UserLibrary;

$userLibrary = new UserLibrary();

extract($result);

?>

<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">
    <?php
    if ($show_add_button && $userLibrary->checkRoleHasPermissions(ucfirst($controller), 'create')) {
      echo add_record_button($controller, '', null, $has_details_listing, $is_multi_row);
    }
    ?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <?= WidgetBase::load('position', 'position_1'); ?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12" style='overflow-x: auto'>
    <table class="table table-striped nowrap" id="datatable" style="width:100%">
      <thead><?= render_list_table_header($keys); ?></thead>
      <tbody>
      </tbody>
    </table>
  </div>
</div>

<script></script>