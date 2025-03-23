<?php

namespace App\Traits\System;

trait CallbackTrait
{

    public function actionAfterInsert(array $post_array, int|null $approval_id, int $header_id): bool
    {
        return true;
    }

    public function actionBeforeInsert(array $postArray): array
    {
        return $postArray;
    }

    protected function actionBeforeEdit(array $postArray): array{
        return $postArray;
   }

   protected function actionAfterEdit(array $postData, int $approveId, int $itemId): bool {
        return true;
   }
}