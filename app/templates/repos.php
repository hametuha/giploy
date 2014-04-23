<?php /** @var \Giploy\Admin $this */ ?>

<h2><span class="genericon genericon-menu"></span> <?php $this->_e('Repository list') ?></h2>

<div class="giploy-info">
    <span class="genericon genericon-info"></span>
    <p>
        <?php printf($this->__('These <a href="%s">registered</a> repositories have <code>Payload URL</code> which must be set at Github.'), admin_url('admin.php?page=giploy-register'), admin_url('admin.php?page=giploy-install')) ?><br />
        <?php printf($this->__('For more detail, see <a href="%s">github documentation</a>.'), 'https://developer.github.com/webhooks/'); ?>
    </p>
</div>

<form method="get" action="<?= admin_url('admin.php') ?>">
    <input type="hidden" name="page" value="giploy-repos">

    <?php
        $table = new \Giploy\RepoList();
        $table->prepare_items();
        $table->display();
    ?>

</form>
