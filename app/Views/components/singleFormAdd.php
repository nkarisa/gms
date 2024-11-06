<?php
  extract($result);

  $form = new \App\Libraries\System\Element();

  $table = $form->create_single_form_add($controller, $fields,'add_form');
  echo $form->add_form(get_phrase('add_'.$controller),$table);

