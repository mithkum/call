<?php
$config = require (__DIR__ . '/config.php');

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $config['username'] || $_SERVER['PHP_AUTH_PW'] != $config['password']) {
    header('WWW-Authenticate: Basic realm="Magnoliyan Video Chat Commander"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Canceled';
    exit;
}

function action_start(){
    global $config, $status, $logConfig;        
    
    if($status){        
        return false;
    }
    spawnProcess($config['start_cmd'], get_log_path());
    $status = is_running();
    redirect("Start executed");
}

function action_stop(){
    global $config, $status, $logConfig;
    
    if(!$status){
        return false;
    }
    
    if(isWin()){
        $command = 'taskkill /F /pid ' . $status['PID'];
        $log = null;
    }
    else{
        $command = $config['stop_cmd'];
        $log = get_log_path();
    }
    spawnProcess($command, $log);
    $status = is_running();
    redirect("Stop executed");
}

function action_index(){
    global $config;
    //$result  = isset($_REQUEST['message']) ? $_REQUEST['message']: null;
    return null;
}

function print_status(){
    global $status;

    if(!$status){
        return '<div class="alert alert-warning">Not running</div>';
    }
    else{
        $html = '<div class="alert alert-success">Running</div><table class="table">';
        $header = "<thead><tr>\n";
        $row = "<tr>\n";
        foreach ($status as $column => $value) {
            $header .= "<th>$column</th>";
            $row .= "<td>$value</td>";
        }
        $header .= "</tr></thead>\n";
        $row .= "</tr>\n";
        $html .= $header . $row ."</table>";
        return $html;
    }
}

function isWin(){
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
}

function get_process_sig(){
    global $config;
    if(isWin()){
        return $config['p_sig'];
    }
    else{
        return '[' . substr($config['p_sig'],0,1) . ']' . substr($config['p_sig'],1);
    }
}

function is_running(){
    global $config;

    if (isWin()) {
        $command = 'WMIC PROCESS WHERE "Caption=\'php.exe\'" get Commandline, Processid, Status, UserModeTime, VirtualSize |findstr ' . get_process_sig();
    } else {
        $command = "ps aux | grep " . get_process_sig();
    }

    $result = shell_exec($command);
    $result = preg_replace('!\s+!', ' ', $result);
    $result = trim($result);
    if(!$result || $result == ""){
        return false;
    }
    $resultArr = explode(" ",$result);
    if (isWin()) {
        $columns = array('Caption','Commandline','PID','UserModeTime','VirtualSize');
    } else {
        $columns = array("USER","PID","%CPU","%MEM","VSZ","RSS","TTY","STAT","START","TIME","COMMAND");
    }
    
    $status = array();
    foreach ($columns as $index => $column) {
        $status[$column] = $resultArr[$index];
    }
    return $status;
}

function get_log_path(){
    global $config, $logConfig;
    
    if($logConfig['status'] == 'ok' || ($logConfig['status'] == 'no_exist' && $logConfig['dir_writable'])){
        return $logConfig['path'];
    }
    return null;
}

function log_status(){
    global $config, $logConfig;
    
    if(!isset($config['log_file']) || $config['log_file'] == ''){
        $logConfig['status'] = 'disabled';
        return false;
    }
    $logConfig['path'] = __DIR__ . DIRECTORY_SEPARATOR . $config['log_file'];

    if(!file_exists($logConfig['path'])){        
        $logConfig['status'] = 'no_exist';        
    }
    else{
        $logConfig['path'] = realpath(__DIR__ . DIRECTORY_SEPARATOR . $config['log_file']);
        if(is_writable($logConfig['path'])){
            $logConfig['status'] = 'ok';
        }
        else{
            $logConfig['status'] = 'no_writable';
        }
    }
    $logConfig['dir'] = dirname($logConfig['path']);
    $logConfig['dir_writable'] = is_writeable($logConfig['dir']);
}

function spawnProcess($command, $logFile = null) {
    if (isWin()) {
        if(isset($logFile)){
            $logFile = '"' . $logFile . '"';
        }
        else{
            $logFile = 'NUL';
        }
        $pcommand = 'start /B ' . $command . ' >> ' . $logFile . ' 2>>&1';
        //echo $pcommand; die();
        shell_exec($pcommand);
    } else {
        if(!isset($logFile)){
            $logFile = '/dev/null';
        }
        else{
            $logFile = '"' . $logFile . '"';
        }
        $pcommand = $command . ' >> ' . $logFile . ' 2>>' . $logFile .' &';
        //echo $pcommand; die();
        pclose(popen($pcommand, 'r'));
    }
}

function functions_enabled(){
    return function_exists('shell_exec') && function_exists('popen') && function_exists('pclose');
}

/**
 * Redirect to a page
 * 
 * @param string $message
 * @param string $url 
 */
function redirect($message = '', $url = 'index.php'){
    //wait a bit for php to start
    sleep(3);
    if($message != ''){
        $url .= '?message=' . urlencode($message);
    }
    header("Location: $url");
    die();
}

$logConfig = array();
log_status();

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'index';
$methodName = 'action_' . $action;
if(!function_exists($methodName)){
    $action = 'index';
    $methodName = 'action_index';
}

$status = is_running();
$result = $methodName();

$functionsEnabled = functions_enabled();

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Magnoliyan Video Chat Commander</title>

        <!-- Bootstrap core CSS -->
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="container">
            <h1>Magnoliyan Video Chat Commander</h1>
            <?php if(!$functionsEnabled){ ?>
            <div class="alert alert-danger">Functions <em>shell_exec, popen, pclose</em> are not enabled in php.ini. Either enable them or use command line to run websocket server.</div>
            <?php } ?>            
            <?php if($result){ ?>
            <div class="alert alert-warning"><?php echo $result; ?></div>
            <?php } ?>
            
            <div class="row">
                <?php if($status){ ?>
                <div class="col-md-4">
                    <!-- WS TESTER -->            
                    <div id="ws_status" style="display: none">
                        <div id="ws_status_ok" class="alert alert-success">
                            Congrats! You should be able to use <em>Magnoliyan Video Chat</em> now.<br>
                            Please update examples source code with valid <strong>wsURL</strong> parameter and visit <a href="../../client/demos/simple/" target="_blank">here</a>
                        </div>
                        <div id="ws_status_err" class="alert alert-danger">Error! Unable to connect to Chat server, please check if the correct parameters are specified and if the port is open on a firewall.<br>
                            Use <a href="http://www.ipfingerprints.com/portscan.php" target="_blank">this</a> or <a href="https://www.google.com/search?q=online+port+scanner" target="_blank">similar tool</a> to scan if the port is available.
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">Websocket parameters</div>
                        <div class="panel-body">
                        <form class="form" role="form">
                            <div class="form-group">
                                <label for="ws_domain" class="control-label">Domain</label>
                                <input type="text" id="ws_domain" class="form-control" placeholder="domain" value="<?php echo $_SERVER['SERVER_NAME'];?>">
                            </div>
                            <div class="form-group" class="control-label">
                                <label for="ws_port">Port</label>
                                <input type="number" id="ws_port" class="form-control" placeholder="port" value="8080">
                            </div>
                            <div class="form-group">
                                <button id="ws_test" type="submit" class="btn btn-primary">Test Connection</button>
                            </div>
                        </form>                        
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="col-md-8">
                    <!-- SERVER STATUS -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Status</h3>
                        </div>
                        <div class="panel-body">                     
                            <?php
                                    echo print_status();
                                    if($functionsEnabled && $status){ ?>
                            <a href="index.php?action=stop" class="btn btn-danger active execBtn" role="button">Stop</a>
                            <a href="../../client/demos/simple/" class="btn btn-default" role="button" target="_blank">Examples</a>
                            <?php }elseif($functionsEnabled){ ?>
                            <a href="index.php?action=start" class="btn btn-success active execBtn" role="button">Start</a>
                            <a href="index.php" class="continue" style="display: none">Click here if execution freezes</a>
                            <?php } ?>
                        </div>
                    </div>                    
                </div>               
            </div> <!-- END ROW -->            
            <?php if($logConfig['status'] != 'disabled'){ ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">Log file</h3>                    
                        </div>
                        <div class="panel-body">
                            <h4><code><?php echo htmlspecialchars($logConfig['path']);?></code></h4>
                            <?php if($logConfig['status'] != 'no_exist'){ ?>
                            <textarea class="form-control" readonly="true" rows="10" id="logTA">
                                <?php echo htmlspecialchars(file_get_contents($logConfig['path'])); ?>
                            </textarea>
                            <?php }
         switch ($logConfig['status']) {
            case 'no_writable':
        ?>
                            <div class="alert alert-danger">Log file is not writable</div>
        <?php                    
                 break;
            case 'no_exist':
                if(!$logConfig['dir_writable']){
        ?>
                            <div class="alert alert-danger">Log directory <code><?php echo htmlspecialchars($logConfig['dir']);?></code> is not writable</div>
        <?php                
                }
                 break;     
             default:
                 break;
         }
                                  ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>            
        </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
        <script>
            function testWs(domain, port){
                if(!window.WebSocket){
                    alert('Your browser does not support websocket.');
                    return false;
                }
                var socket = new WebSocket('ws://' + domain + ':' + port);
                //after socket is opened
                socket.onopen = function() {
                    $('#ws_status,#ws_status_ok').show();
                    $('#ws_test').button('reset');
                };
                //ws error
                socket.onerror = function(err) {
                    $('#ws_status,#ws_status_err').show();
                    $('#ws_test').button('reset');
                };
                //on close
                socket.onclose = function(){
                    $('#ws_status,#ws_status_err').show();
                    $('#ws_test').button('reset');
                }                
            };
            
            $(document).ready(function(){
                $('#ws_test').click(function(){
                    $('#ws_status,#ws_status_ok,#ws_status_err').hide();
                    $(this).button('loading');
                    testWs($('#ws_domain').val(),$('#ws_port').val());
                    return false;
                });
                $('.execBtn').click(function(){
                    $(this).text('executing, please wait...');
                    $('.continue').show();
                });                
                var $logTA = $('#logTA');
                $logTA.scrollTop($logTA.get(0).scrollHeight - $logTA.height());
            });
          </script>        
    </body>
</html>