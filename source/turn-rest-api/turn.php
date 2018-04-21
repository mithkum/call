<?php


class TurnRestApi {
    protected $config = array();
    
    function __construct(array $config) {
        $this->config = $config;
    }
    
    /**
     * Generate credentials
     * 
     * @param string $username
     * @return array|false
     */
    public function getCredentials($username){
        if(!in_array($username, $this->config['users'])){
            return false;
        }
        $ts = time() + $this->config['ttl'];
        $username = $ts . ':' . $username;
        $password = base64_encode(hash_hmac('sha1', $username, $this->config['secret'], TRUE));        
        
        return array(
            'username'  => $username,
            'password'  => $password,
            'uris'       => $this->config['uris'],
            'ttl'        => $this->config['ttl']
        );
    }

}

$config = require_once 'config.php';

// In cli-mode
if (php_sapi_name() == "cli") {    
    $shortopts = ""; // Optional value
    $longopts  = array("username::");
    $options = getopt($shortopts, $longopts);
    if(!isset($options['username'])){
        die("\nno username provided\n");
    }
    $username = $options['username'];
    $api = new TurnRestApi($config);
    $result = $api->getCredentials($username);
    if(!$result){
        die("\nInvalid credentials\n");
    }
    print_r($result);
    echo "\nCompare with\n";
    echo('echo -n "' . $result['username'] . '" | openssl dgst -sha1 -hmac "' . $config['secret'] . '" -binary | base64');
    echo "\n";
    die('');    
}

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

if(!$config['allowNoOrigin']){
    if(!isset($_SERVER['HTTP_ORIGIN'])){
        echo json_encode(array('error' => 'No origin provided'));
        die();
    }    
    $origin = $_SERVER['HTTP_ORIGIN'];
    if(!in_array($origin, $config['origins'])){
        echo json_encode(array('error' => 'Origin not allowed'));
        die();
    }    
}

if(!isset($_REQUEST['username']) || !in_array($_REQUEST['username'], $config['users'])){
    echo json_encode(array('error' => 'Username not allowed'));
    die();
}
$username = $_REQUEST['username'];

$api = new TurnRestApi($config);
$result = $api->getCredentials($username);

if(!$result){
    echo json_encode(array('error' => 'Invalid credentials'));
    die();    
}

echo json_encode($result);
