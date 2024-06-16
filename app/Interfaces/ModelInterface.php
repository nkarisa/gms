<?php 

namespace App\Interfaces;

interface ModelInterface {
    public function all():array;
    public function one($id):array;
    public function append_creator_id();
}