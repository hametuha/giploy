<?php

namespace Giploy\Git;


/**
 * Repository model
 *
 * @package Giploy\Git
 * @property-read bool $writable
 * @property-read string $path
 * @property-read string $abspath
 * @property-read string $branch
 * @property-read string $dir
 * @property-read string $type
 * @property-read string $commit
 * @property-read string $hash
 * @property-read string $remote
 * @property-read string $payload_url
 */
class Repo extends Base
{

    /**
     * Relative Path from ABSPATH
     *
     * @var string
     */
    private $path = '';

    /**
     * Constructor
     *
     * @param $path
     */
    public function __construct($path){
        $this->path = $path;
    }

    /**
     * Pull
     *
     * @return string
     */
    public function pull(){
        try{
            // Do pull
            $out = $this->get_stdout($this->abspath, 'pull');
            // Check submodules
            $modules_out = trim($this->get_stdout($this->abspath, 'submodule'));
            if( !empty($modules_out) ){
                // Submodules exist. Try update them.
                $out .= $this->get_stdout($this->abspath, 'submodule', array('update', '--init'));
            }
        }catch ( \Exception $e ){
            $out = $e->getMessage();
        }
        return $out;
    }

    /**
     * Getter
     *
     * @param string $key
     * @return mixed|null|string|void
     */
    public function __get($key){
        switch($key){
            case 'writable':
                return is_writable($this->abspath);
                break;
            case 'path':
                return $this->path;
                break;
            case 'abspath':
                return ABSPATH.DIRECTORY_SEPARATOR.$this->path;
                break;
            case 'dir':
                return basename($this->path);
                break;
            case 'type':
                if( preg_match('/wp-content\/plugins/u', $this->path) ){
                    return $this->__('Plugin');
                }elseif( preg_match('/wp-content\/themes/u', $this->path) ){
                    return $this->__('Theme');
                }else{
                    return $this->__('Other');
                }
                break;
            case 'hash':
                return $this->get_commit_hash($this->abspath);
                break;
            case 'commit':
                return $this->get_commit_log($this->abspath);
                break;
            case 'branch':
                return $this->get_branch($this->abspath);
                break;
            case 'remote':
                return $this->get_remote($this->abspath);
                break;
            case 'payload_url':
                if( get_option('rewrite_rules') ){
                    return home_url('/giploy/'.$this->path);
                }else{
                    return add_query_arg(array(
                        'giploy' => $this->path,
                    ), home_url());
                }
                break;
            default:
                return parent::__get($key);
                break;
        }
    }
}