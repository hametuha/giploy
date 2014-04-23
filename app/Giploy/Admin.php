<?php

namespace Giploy;


use TQ\Git\Cli\Binary;


/**
 * Admin class
 *
 * @package Giploy
 */
class Admin extends Pattern\Singleton
{
    /**
     * Called when init hook if fired.
     */
    public function initialized(){
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'admin_init'));
        // Register scripts
        $assets_dir = plugin_dir_url(dirname(__DIR__)).'assets';
        add_action('init', function () use ($assets_dir) {
            wp_register_style('genericon', $assets_dir.'/vendor/genericons/genericons.css', array(), '3.0.3', 'screen');
        });
        // Enqueue admin scirpts and css
        $version = self::VERSION;
        $self = $this;
        add_action('admin_enqueue_scripts', function($path) use ($assets_dir, $version, $self) {
            // CSS
            wp_enqueue_style('giploy-admin', $assets_dir.'/css/giploy-admin.css', array('genericon'), $version, 'screen');
            // Script
            switch( $path ){
                case 'giploy_page_giploy-repos':
                    wp_enqueue_script('giploy-repo-table', $assets_dir.'/js/giploy-table.min.js', array('jquery'), $version);
                    wp_localize_script('giploy-repo-table', 'GiployVar', array(
                        'confirm' => $self->__('Are you sure to unregister this repository? Don\'t worry, directory it self will never be deleted.'),
                        'register' => $self->__('Please register this Payload URL to your repository.'),
                    ));
                    break;
            }
        });
    }

    /**
     * Register admin menu
     */
    public function admin_menu(){
        // Start session on admin screen
        if( !session_id() ){
            session_start();
        }
        // Add pages.
        add_menu_page('Giploy', 'Giploy', 'install_themes', 'giploy', array($this, 'load'), '', 66.6);
        if( $this->get_git() ){
            // Repos
            add_submenu_page('giploy', $this->__('All Repositories'), $this->__('Repositories'), 'install_themes', 'giploy-repos', array($this, 'load'));
            // Register
            add_submenu_page('giploy', $this->__('Register Git Repository'), $this->__('Register'), 'install_themes', 'giploy-register', array($this, 'load'));
            // Install
            //add_submenu_page('giploy', $this->__('Install Repository from Github'), $this->__('Install'), 'install_themes', 'giploy-install', array($this, 'load'));
        }
    }

    /**
     * Load Template
     */
    public function load(){
        if( isset($_GET['page']) ){
            echo '<div class="wrap">';
            switch( $_GET['page'] ){
                case 'giploy':
                    include dirname(__DIR__).'/templates/setting.php';
                    break;
                case 'giploy-repos':
                    include dirname(__DIR__).'/templates/repos.php';
                    break;
                case 'giploy-register':
                    include dirname(__DIR__).'/templates/register.php';
                    break;
                case 'giploy-install':
                    include dirname(__DIR__).'/templates/install.php';
                    break;
                default:
                    // Wrong. do nothing.
                    break;
            }
            echo '</div>';
        }
    }

    /**
     * Admin save action.
     */
    public function admin_init(){
        if( isset($_REQUEST['_giploynonce']) ){
            $nonce = (string)$_REQUEST['_giploynonce'];
            $redirect = admin_url('admin.php?page=giploy');
            if( wp_verify_nonce($nonce, 'giploy_path') ){
                // Register git binary path
                if(update_option('giploy_git_path', $_REQUEST['git-path'])){
                    $this->add_message($this->__('Update Git binary path.'));
                }else{
                    $this->add_message($this->__('Nothing has been changed.'), true);
                }
            }elseif( wp_verify_nonce($nonce, 'giploy_register') ){
                // Register local repository
                try{
                    $path = untrailingslashit($this->sanitize($_REQUEST['repo-name']));
                    $abs_path = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$path;
                    if( !is_dir($abs_path) ){
                        throw new \Exception(sprintf($this->__('<code>%s</code> is not direcotry.'), esc_html($abs_path)));
                    }
                    $git_folder = trailingslashit($abs_path).'.git';
                    if( !is_dir($git_folder) ){
                        throw new \Exception(sprintf($this->__('<code>%s</code> doesn\'t seem to be a git repository.'), esc_html($abs_path)));
                    }
                    $path_segments = explode(DIRECTORY_SEPARATOR, $path);
                    $first_seg = array_shift($path_segments);
                    switch($first_seg){
                        case 'themes':
                        case 'plugins':
                        $this->save_repo('wp-content'.DIRECTORY_SEPARATOR.$first_seg.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path_segments), $first_seg);
                            break;
                        default:
                            $this->save_repo($first_seg.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path_segments), 'other');
                            break;
                    }
                    $this->add_message(sprintf($this->__('Repository added. You can find it on <a href="%s">your list</a>.'), admin_url('admin.php?page=giploy-repos')));
                }catch ( \Exception $e ){
                    $this->add_message($e->getMessage(), true);
                }
                $redirect = admin_url('admin.php?page=giploy-register');
            }elseif( wp_verify_nonce($nonce, 'giploy_delete') ){
                // Delete path
                $path_to_delete = $_REQUEST['path'];
                $repos = $this->repo;
                $new_repos = array();
                foreach( $repos as $path ){
                    if( $path !== $path_to_delete ){
                        $new_repos[] = $path;
                    }
                }
                if( count($new_repos) == count($repos) ){
                    $this->add_message(sprintf($this->__('Mmm...<code>%s</code> was not registered.'), $path_to_delete), true);
                }else{
                    update_option('giploy_repo', $new_repos);
                    $this->add_message(sprintf($this->__('<code>%s</code> was successfully deleted.'), $path_to_delete));
                }
                $redirect = admin_url('admin.php?page=giploy-repos');
            }
            wp_redirect($redirect);
            exit;
        }
    }

    /**
     * Detect git binary path
     *
     * @return string
     */
    public function detect_binary_path(){
        return Binary::locateBinary();
    }

    /**
     * @param $path
     * @param string $type
     * @throws \Exception
     */
    public function save_repo($path, $type = 'plugins'){
        switch( $type ){
            case 'themes':
            case 'plugins':
                if( false !== array_search($path, $this->repo) ){
                    throw new \Exception($this->__('This path is already registered'));
                }
                $new_array = $this->repo;
                $new_array[] = $path;
                sort($new_array);
                break;
            default:
                throw new \Exception($this->__('This type is not allowed.'));
                break;
        }
        update_option('giploy_repo', $new_array);
    }

    /**
     * Show admin message
     */
    public function admin_notices(){
        if( current_user_can('install_themes') && !$this->get_git() ){
            // Warning if git is not installed.
            printf('<div class="error"><p><strong>[Giploy Error]</strong> %s</p></div>', sprintf($this->__('Git binary path is not defined. Please set on <a href="%s">setting page</a>.'), admin_url('admin.php?page=giploy')));
        }
        if( session_id() && isset($_SESSION['giploy']) ){
            // Show message is exits.
            foreach( $_SESSION['giploy'] as $key => $messages ){
                if( !empty($messages) ){
                    printf('<div class="%s"><p>%s</p></div>', $key, implode('<br />', $messages));
                    $_SESSION['giploy'][$key] = array();
                }
            }
        }
    }

    /**
     * Save message on session.
     *
     * @param string $string
     * @param bool $error
     */
    public function add_message($string, $error = false){
        if( session_id() ){
            if( !isset($_SESSION['giploy']) ){
                $_SESSION['giploy'] = array(
                    'error' => array(),
                    'updated' => array()
                );
            }
            $key = $error ? 'error' : 'updated';
            $_SESSION['giploy'][$key][] = $string;
        }
    }
}
