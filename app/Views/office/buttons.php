<?php
$width = 33;
if ($session->system_admin) {
  $width = 25;
}
?>
<style>
  * {
    box-sizing: border-box
  }

  /* Set height of body and the document to 100% */
  body,
  html {
    height: 100%;
    margin: 0;
    font-family: Arial;
  }

  /* Style tab links */
  .tablink {
    background-color: #555;
    color: white;
    float: left;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 14px 16px;
    font-size: 17px;
    width:
      <?= $width . '%'; ?>
    ;
  }

  .tablink:hover {
    background-color: #777;
  }

  /* Style the tab content (and add height:100% for full page content) */
  .tabcontent {
    color: black;
    display: none;
    padding: 100px 20px;
    height: 100%;
  }

  #fcp_offices {
    background-color: white;
  }

  #cluster_offices {
    background-color: white;
  }

  #base_or_regions {
    background-color: white;
  }

  #country {
    background-color: white;
  }
</style>

<?php if($action == 'list'):?>
<div class="row" style="margin-top: 50px;margin-bottom: 50px;">
  <div class="col-xs-12">
    <button class="tablink" onclick="openPage('1', this, 'blue')"
      id="defaultOpen"><?= get_phrase('fcp_offices', 'FCP Offices'); ?></button>
    <button class="tablink"
      onclick="openPage('2', this, 'black')"><?= get_phrase('cluster_offices', 'Cluster Offices'); ?></button>
    <button class="tablink"
      onclick="openPage('3', this, 'orange')"><?= get_phrase('cohort_offices', 'Bases or Regions'); ?></button>

    <?php if ($session->system_admin) { ?>
      <button class="tablink" onclick="openPage('4', this, 'brown')"><?= get_phrase('country', 'Country'); ?></button>
    <?php } ?>
  </div>

  <div class="col-xs-12" style = "margin-top: 30px;">
    <div class='col-xs-4'>
      <!-- Populate clusters for enabling mass update of moving fcp to clusters -->
      <select id="cluster" name="header[fk_cluster_id]"
        class="form-control master input_office fk_user_id select2 select2-offscreen visible">
        <option value='0'><b><?= get_phrase('select_cluster'); ?></b></option>

        <?php
        $cluster_ids = array_column($cluster_offices, 'office_id');
        $cluster_names = array_column($cluster_offices, 'office_name');
        $cluster_office_ids_and_cluster_names = array_combine($cluster_ids, $cluster_names);

        foreach ($cluster_office_ids_and_cluster_names as $key => $cluster_names) { ?>
          <option value='<?= $key; ?>'><?= $cluster_names; ?></option>
        <?php } ?>
      </select>
    </div>

    <div class='col-xs-2'>
      <button disabled id='click_move_fcps'
        class='btn btn-primary btn-click_move_fcps'><?= get_phrase('click_move_fcps') ?></button>
    </div>
    <div id='update_msg' class='col-xs-4 '></div>
  </div>
</div>
<?php endif;?>