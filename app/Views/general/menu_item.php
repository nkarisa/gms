<li class="sep"></li>
<li class="menu_tab <?=strtolower($menu);?>">
    <a href="<?=base_url() . strtolower($menu);?>/list">
        <i class="<?=$icon;?>"></i>
        <span><?=get_phrase(strtolower($menu_name));?></span>
    </a>
</li>