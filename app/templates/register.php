<?php /** @var \Giploy\Admin $this */ ?>

<h2><span class="genericon genericon-edit"></span> <?php $this->_e('Register Repository') ?></h2>

<p>
    <?php $this->_e('You can register git repository on your server.') ?><br />
    <?php $this->_e('Select one from plugins and themes list below.') ?>
</p>

<form method="post" action="<?= admin_url('admin.php?page=giploy-register') ?>">
    <?php wp_nonce_field('giploy_register', '_giploynonce') ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="repo-name"><?php $this->_e('Repository to add') ?></label></th>
            <td>
                <select name="repo-name" id="repo-name">
                    <option value="" selected>------</option>
                    <?php
                        foreach( array(
                            'themes' => $this->__('Themes'),
                            'plugins' => $this->__('Plugins'),
                        ) as $type => $label ):
                    ?>
                        <?php
                            $dir = ABSPATH.'/wp-content/'.$type.'/';
                            $git_dir = array();
                            foreach( scandir($dir) as $file ){
                                $path = $dir.$file;
                                if( is_dir($path) && file_exists($path.'/.git') && !$this->is_registered($file, $type) ){
                                    $git_dir[] = $file;
                                }
                            }
                            if( !empty($git_dir) ):
                        ?>
                        <optgroup label="<?= $label ?>">
                            <?php foreach($git_dir as $git): ?>
                                <option value="<?= esc_attr("{$type}/{$git}") ?>"><?= esc_html($git) ?></option>
                            <?php endforeach ?>
                        </optgroup>
                        <?php endif; ?>
                    <?php endforeach ?>
                </select>
                <p class="description">
                    <?php $this->_e('Please select installed git repository. If nothing is displayed, that means no git repository in your server.') ?>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    <?php submit_button(__('Register')) ?>
</form>
