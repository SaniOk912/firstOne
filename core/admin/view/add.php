<?php
?>
<form class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?=$this->adminPath . $this->action?>" enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full">
        <label for="" class="vg-wrap vg-full file_upload vg-left">
            <input id="" type="file" name="img" class="single_img">
        </label>
    </div>
<!--    <input type="text" name="id" class="vg-input vg-text vg-firm-color1" value="10">-->
    <input type="text" name="username" class="vg-input vg-text vg-firm-color1" value="name">
    <input type="text" name="password" class="vg-input vg-text vg-firm-color1" value="password">
    <?php if($this->data):?>
        <input type="hidden" name="<?=$this->columns['id_row']?>" value="<?=$this->data[$this->columns['id_row']]?>">
    <?php endif;?>
    <input type="hidden" name="table" value="<?=$this->table?>">

    <input type="submit" class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button" value="Сохранить">
</form>