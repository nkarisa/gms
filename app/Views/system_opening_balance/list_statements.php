<table class="table table-striped" id="tbl_list_statements">
                    <thead>
                        <tr>
                            <th><?=get_phrase('action');?></th>
                            <th><?=get_phrase('file_name');?></th>
                            <th><?=get_phrase('file_size');?></th>
                            <th><?=get_phrase('last_modified_date');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bank_statements_uploads as $bank_statements_upload){
                            if(!isset($bank_statements_upload['attachment_url'])) continue;
                            ?>
                            <tr>
                        <td><i style="cursor: pointer;" class="fa fa-trash-o delete_statement" onClick="delete_statement('<?=$bank_statements_upload['attachment_id'];?>')" id="<?=$bank_statements_upload['attachment_url'];?>"></i></td>
                    
                                <?php 
                                    $objectKey = $bank_statements_upload['attachment_url'].'/'.$bank_statements_upload['attachment_name'];
                                    $url = $this->config->item('upload_files_to_s3')?$this->grants_s3_lib->s3_preassigned_url($objectKey):$this->attachment_library->get_local_filesystem_attachment_url($objectKey);
                                ?>
                                <td><a target='__blank' href='<?=$url;?>'><?=$bank_statements_upload['attachment_name'];?></a></td>
                                <td><?=formatBytes($bank_statements_upload['attachment_size']);?></td>
                                <td><?=$bank_statements_upload['attachment_last_modified_date'];?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>