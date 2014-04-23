<?php

namespace Giploy\Git;

use Ratchet\Wamp\Exception;
use TQ\Git\Cli\Binary;

/**
 * Base class
 *
 * @package Giploy\Git
 * @property-read array $repo
 */
class Base
{
    /**
     * Plugin version.
     */
    const VERSION = '1.0';

    /**
     * Git binary path
     *
     * @return string
     */
    public function get_git(){
        return (string)get_option('giploy_git_path', '');
    }

    /**
     * Detect if binary is valid.
     *
     * @return bool
     */
    public function is_valid_binary(){
        try{
            $version_string = $this->version();
            return (bool)preg_match('/git version/', $version_string);
        }catch ( \Exception $e ){
            return false;
        }
    }

    /**
     * Show version information.
     *
     * @return string
     */
    public function version(){
        try{
            return $this->get_stdout(ABSPATH, 'version');
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * Execute command
     *
     * @param string $where
     * @param string $command
     * @param array $arguments
     * @return \TQ\Vcs\Cli\CallResult
     * @throws \RuntimeException
     */
    protected function execute($where, $command, $arguments = array()){
        if( !$this->get_git() ){
            throw new \RuntimeException($this->__('Git binary path is undefined.'));
        }
        $binary = new Binary($this->get_git());
        $commands = $binary->createCall($where, $command, $arguments);
        return $commands->execute();
    }

    /**
     * Execute command and get standard output
     *
     * @param string $where
     * @param string $command
     * @param array $arguments
     * @return string
     */
    protected function get_stdout($where, $command, $arguments = array()){
        $result = $this->execute($where, $command, $arguments);
        return $result->getStdOut();
    }

    /**
     * Return commit hash
     *
     * @param string $where
     * @return bool|string
     */
    protected function get_commit_hash($where){
        try{
            $std = $this->get_stdout($where, 'log', array('-n' => 1));
            if( !preg_match('/commit ([a-z0-9]+)/u', $std, $match) ){
                return false;
            }
            return $match[1];
        }catch ( \Exception $e ){
            return false;
        }
    }

    /**
     * Return commit log
     *
     * @param string $where
     * @return bool|string
     */
    protected function get_commit_log($where){
        try{
            $std = $this->get_stdout($where, 'log', array('-n' => 1));
            if( 0 === strpos($std, 'fatal:')){
                return false;
            }
            return $std;
        }catch ( \Exception $e ){
            return false;
        }
    }

    /**
     * Return string
     *
     * @param string $where
     * @return bool|string
     */
    protected function get_branch($where){
        try{
            $std = $this->get_stdout($where, 'branch');
            $match = array();
            if( !preg_match('/^\* (.*)$/um', $std, $match) ){
                return false;
            }
            return $match[1];
        }catch ( \Exception $e ){
            return false;
        }
    }

    /**
     * Returns remote list
     *
     * @param string $where
     * @return array
     */
    protected function get_remote($where){
        try{
            $std = $this->get_stdout($where, 'remote', array('-v'));
            $match = array();
            $remotes = array();
            if( preg_match_all('/^(.*)\t(.*)\(.*\)$/um', $std, $match) ){
                foreach( $match[1] as $index => $key ){
                    if( !isset($remotes[$key]) ){
                        $remotes[$key] = $match[2][$index];
                    }
                }
            }
            return $remotes;
        }catch ( \Exception $e ){
            return array();
        }
    }

    /**
     * Get relative path from ABSPATH
     *
     * @param string $path
     * @param string $type theme, plugin
     * @return string
     */
    protected function get_path($path, $type = ''){
        $path = ltrim(untrailingslashit($this->sanitize($path)), DIRECTORY_SEPARATOR);
        switch($type){
            case 'themes':
            case 'plugins':
                $path = 'wp-content'.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$path;
                break;
            default:
                // path is path. that's all.
                break;
        }
        return $path;
    }

    /**
     * Sanitize path
     *
     * Prepend directory traversal, remove null byte,
     *
     * @param string $path
     * @return string
     */
    protected function sanitize($path){
        return ltrim(str_replace('../', '', str_replace("\0", "", (string)$path)));
    }

    /**
     * Detect if path is registered as repository
     *
     * @param string $path
     * @param string $type
     * @return bool
     */
    public function is_registered($path, $type = ''){
        $path = $this->get_path($path, $type);
        return false !== array_search($path, $this->repo);
    }

    /**
     * Detect if github is connected
     *
     * @return bool
     */
    public function github_connected(){
        return false;
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
     * Short hand for _e
     *
     * @param string $str
     */
    public function _e($str){
        _e($str, 'giploy');
    }

    /**
     * Getter
     *
     * @param string $key
     * @return mixed|null|void
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
