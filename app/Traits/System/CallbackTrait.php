<?php

namespace App\Traits\System;

trait CallbackTrait
{

    public function actionAfterInsert(array $post_array, int $approval_id, int $header_id): bool
    {
        return true;
    }

    public function actionBeforeInsert(array $postArray): array
    {
        return $postArray;
    }

   protected function postApprovalActionEvent(array $item):void{

   }

    protected function actionBeforeEdit(array $postArray): array{
        return $postArray;
   }

   protected function actionAfterEdit(): bool {
        return true;
   }
}