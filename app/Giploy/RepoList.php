<?php

namespace Giploy;
use Giploy\Git\Repo;


/**
 * List table for repositories
 *
 * @package Giploy
 * @property-read array $repo
 */
class RepoList extends \WP_List_Table
{
    /**
     * Constructor
     */
    public function __construct(){
        parent::__construct(array(
            'singular' => 'repo',
            'plural' => 'repos',
            'ajax' => false,
        ));
    }

    /**
     * Register column
     *
     * @return array
     */
    public function get_columns(){
        return array(
            'dir' => $this->__('Directory'),
            'type' => $this->__('Type'),
            'permission' => $this->__('Permission'),
            'remote' => $this->__('Remote'),
            'branch' => $this->__('Branch'),
            'commit' => $this->__('Latest Commit'),
        );
    }

    /**
     * Create repository list
     */
    public function prepare_items(){
        // Set column header
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );
        // Prepare items
        $repos = array();
        foreach( $this->repo as $path ){
            $repos[] = new Repo($path);
        }
        // Set items
        $this->items = $repos;
        $this->set_pagination_args(array(
            'total_items' => count($repos),
            'per_page' => '-1'
        ));
    }

    /**
     * No items.
     */
    public function no_items(){
        echo $this->__('No repository is registered.');
    }

    /**
     * Override column class
     *
     * @return array
     */
    public function get_table_classes(){
        return array('widefat', $this->_args['plural']);
    }

    /**
     * Render rows
     *
     * @param Repo $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name){
        switch($column_name){
            case 'dir':
                $row = sprintf('%s', $item->dir);
                $row .= $this->row_actions(array(
                    'endpoint' => sprintf('<a href="%s">%s</a>', $item->payload_url, $this->__('Get Payload URL')),
                    'delete' => sprintf('<a href="%s">%s</a>', add_query_arg(array(
                        'page' => 'giploy-repos',
                        'path' => $item->path,
                        '_giploynonce' => wp_create_nonce('giploy_delete'),
                    ), admin_url('admin.php')), $this->__('Unregister')),
                ));
                return $row;
                break;
            case 'permission':
                if( $item->writable ){
                    return sprintf('<span class="ok"><span class="genericon genericon-checkmark"></span> %s</span>', $this->__('Writable'));
                }else{
                    return sprintf('<span class="error"><span class="genericon genericon-close"></span> %s</span>', $this->__('Not writable. Change permission.'));
                }
                break;
            case 'type':
                return sprintf('<strong>%s</strong>', $item->type);
                break;
            case 'remote':
                $remote = $item->remote;
                if( $remote ){
                    $list = array();
                    foreach($remote as $r => $url){
                        $list[] = sprintf('<a href="%s">%s</a>', esc_attr($url), $r);
                    }
                    return implode(', ', $list);
                }else{
                    return '<small style="color:lightgrey;">---</small>';
                }
                break;
            case 'branch':
                $branch = $item->branch;
                if( $branch ){
                    return sprintf('<small>%s</small>', $branch);
                }else{
                    return '<small style="color:lightgrey;">---</small>';
                }
                break;
            case 'commit':
                $hash = $item->hash;
                $commit = $item->commit;
                if($hash){
                    return sprintf('<code>%s</code><br /><small>%s</small><pre>%s</pre>',
                        $hash, $this->__('Click hash and toggle detailed log.'), $commit);
                }else{
                    return '<small style="color:lightgrey;">---</small>';
                }
                break;
        }
    }

    /**
     * Short hand for __
     *
     * @param string $str
     * @return string
     */
    public function __($str){
        return __($str, 'giploy');
    }

    /**
     * Getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key){
        switch($key){
            case 'repo':
                return get_option('giploy_repo', array());
                break;
            default:
                return null;
                break;
        }
    }
}
