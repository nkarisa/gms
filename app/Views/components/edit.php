<?php
  extract($result);

  $form = new \App\Libraries\System\Element();

  $table = $form->create_single_form_add($controller, $fields,'edit_form');
  echo $form->add_form('Edit '.$controller,$table);

