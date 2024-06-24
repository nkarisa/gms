<?php 

namespace App\Cells;

class Content
{
    public function show(array $params): string
    { 
        return view($params['output']['views_dir']."/".$params['output']['page_name'], ['result' => $params['output']['result']]);   
    }
}