<?php /** @var \Giploy\Admin $this */ ?>

<h2><span class="genericon genericon-github"></span> <?php $this->_e('Giploy Setting') ?></h2>
<form method="post" action="<?= admin_url('admin.php?page=giploy') ?>">
    <?php wp_nonce_field('giploy_path', '_giploynonce') ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="git-path"><?php $this->_e('Git binary path') ?></label> </th>
            <td>
                <input class="regular-text" type="text" name="git-path" id="git-path" value="<?= esc_attr($this->get_git()) ?>" />
                <?php if( $this->is_valid_binary() ): ?>
                    <p>
                        <span class="ok"><span class="genericon genericon-checkmark"></span> O.K.</span>
                        <code><?= $this->version() ?></code>
                    </p>
              <?php else: ?>
                    <p style="color: lightgrey;">
                        <span class="ng"><span class="genericon genericon-unapprove"></span> Not Found</span>
                    </p>
                    <?php if( $path = $this->detect_binary_path() ): ?>
                        <p><?php $this->_e('Suggestion') ?>: <code><?= $path ?></code></p>
                    <?php endif ?>
                    <p class="description">
                        <?php $this->_e('Git must be installed on your server.') ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php submit_button(__('Update')) ?>
</form>

<h2><span class="genericon genericon-help"></span> <?php $this->_e('What is this?') ?></h2>
<p>
    <?= $this->__('This plugin helps github hosted theme or plugin for pulling automatically. You ought to know many things.') ?>
</p>
<dl class="giploy-faq">
    <dt><?= $this->__('Install Git on your server.') ?></dt>
    <dd><?= $this->__('Git must be installed on your server. Unfortunately, this plugin cannot do that.') ?></dd>
    <dt><?= $this->__('Clone repository on your server.') ?></dt>
    <dd><?= $this->__('Typical usage is for github hosted theme. Only themes and plugins which have <code>.git</code> are listed as registable directories.') ?></dd>
    <dt><?= $this->__('Get Payload URL and register it as service hook on github.') ?></dt>
    <dd><?= $this->__('Once repository is registered, you can get Payload URL. Go to github\'s repository setting and enter it as sevice hook.') ?></dd>
</dl>
<p class="description">
    <?= $this->__('If you are unsure about all these things, stop this plugin and uninstall it. This is a highly excerimental plugin.') ?>
</p>

<p class="giploy-sample">
    <img src="<?= plugin_dir_url(dirname(__DIR__)) ?>assets/images/example.png" alt="sample" />
</p>