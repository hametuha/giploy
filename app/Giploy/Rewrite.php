<?php

namespace Giploy;


use Giploy\Git\Repo;
use Ratchet\Wamp\Exception;

class Rewrite extends Pattern\Singleton
{
    /**
     * Rewrite rules
     *
     * @var string
     */
    private $rewrite = 'giploy/(.+)';

    /**
     * CIDR of Github service hook
     *
     * @var string
     */
    private $cidr = '192.30.252.0/22';

    /**
     * Called when init hook if fired.
     */
    public function initialized(){
        if( $this->get_git() ){
            // Add query vars
            add_filter('query_vars', function($vars){
                $vars[] = 'giploy';
                return $vars;
            });
            // Add rewrite rule
            add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rules'));
            // Flush rules if rewrite does not exits
            add_action('admin_init', array($this, 'flush_rules'));
            // Get hook
            add_action('pre_get_posts', array($this, 'pre_get_posts'));
        }
    }

    /**
     * Flush rewrite if not exits
     */
    public function flush_rules(){
        $rewrite = get_option('rewrite_rules');
        if( $rewrite && !isset($rewrite[$this->rewrite]) ){
            flush_rewrite_rules();
        }
    }

    /**
     * Generate rewrite rule
     *
     * @param \WP_Rewrite $wp_rewrite
     */
    public function generate_rewrite_rules( &$wp_rewrite ){
        $wp_rewrite->rules = array(
                $this->rewrite => 'index.php?giploy=$matches[1]'
            ) + $wp_rewrite->rules;
    }

    /**
     * Parse request
     *
     * @param \WP_Query $wp_query
     */
    public function pre_get_posts( \WP_Query $wp_query ){
        if( $wp_query->is_main_query() && ($path = $wp_query->get('giploy')) ){
            nocache_headers();
            try{
                $path = trim($path, '/');
                // Check IP
                $this->check_ip();
                // Check if it is registered.
                if( !$this->is_registered($path) ){
                    throw new \Exception(sprintf($this->__('%s is not registered.'), $path), 404);
                }
                // Get repository
                $repo = new Repo($path);
                $branch = $repo->branch;
                // Get data
                $headers = $this->get_request_headers();
                if( isset($headers['X-Github-Event']) && 'push' == $headers['X-Github-Event'] ){
                    // Event is push
                    if( 'application/json' == $headers['Content-Type'] ){
                        $data = json_decode(@file_get_contents('php://input'), true);
                    }else{
                        $data = $_POST;
                    }
                    // Do action
                    do_action('giploy_pull', $headers, $body);
                    // Pull
                    $stdout = $repo->pull();
                    // Make mail string
                    $data_str = var_export($data, true);
                    $headers = var_export($headers, true);
                    $message = $this->__('Giploy get web hook request from github. Details are below.');
                    $footer = $this->__('This message was automatically generaged with plugin Giploy.');
                    $date = sprintf($this->__('Executed timestamp: %s'), current_time('mysql'));
                    $name = get_bloginfo('name');
                    $subject = sprintf($this->__('[Giploy] %s get request #%s'), $name, $headers['X-Github-Delivery']);
                    $body = <<<EOS
To {$name} admin



{$message}

{$date}

## Request Header

```
{$headers}
```

## Request Body

```
{$data_str}
```

## Pull result

```
{$stdout}
```

---

{$footer}

EOS;
                    // Mail.
                    if( apply_filters('giploy_mail', true, $subject, $body) ){
                        if(!wp_mail(get_option('admin_email'), $subject, $body)){
                            error_log('Failed to send mail.'.PHP_EOL.PHP_EOL.$body);
                        }
                    }
                    $response = $this->__('We got web hook. Thank you.');
                }else{
                    $response = '';
                }
                // Show body
                header('Content-Type: text/plain');
                echo $response;
                exit;
            }catch ( \Exception $e ){
                wp_die($e->getMessage(), get_status_header_desc($e->getCode()), array(
                    'response' => $e->getCode()
                ));
            }
        }
    }

    /**
     * Test IP address
     *
     * @throws \Exception
     * @return bool
     */
    private function check_ip(){
        if( WP_DEBUG && '127.0.0.1' == $_SERVER['REMOTE_ADDR'] ){
            return true;
        }
        if( !isset($_SERVER['REMOTE_ADDR'])
            || !$this->cidr_includes($_SERVER['REMOTE_ADDR'], $this->cidr)
        ){
            throw new \Exception($this->__('Your request is from invalid IP address.'), 403);
        }
    }

    /**
     * Test if CIDR includes specified IP
     *
     * @link http://www.php.net/manual/ja/ref.network.php
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function cidr_includes($ip, $cidr) {
        list ($net, $mask) = explode ("/", $cidr);
        $ip_net = ip2long ($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long ($ip);
        $ip_ip_net = $ip_ip & $ip_mask;
        return ($ip_ip_net == $ip_net);
    }

    /**
     * Get HTTP request headers
     *
     * @link http://www.php.net/manual/en/function.getallheaders.php#84262
     * @return array
     */
    private function get_request_headers(){
        $headers = array();
        foreach ($_SERVER as $name => $value){
            if (substr($name, 0, 5) == 'HTTP_'){
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
