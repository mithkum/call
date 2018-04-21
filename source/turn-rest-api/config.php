<?php
$ip = 'your_server_ip';
return array(
    'allowNoOrigin'         => true, //allow access when client does not provide origin header
    'secret'                => 'your_secret', //the same as "static-auth-secret" from /etc/turnserver.conf
    'users'                 => array('your_username'), //usernames
    'origins'               => array('http://www.your_username.com', 'https://www.your_username.com'), //allowed origins
    'uris'                  => array("turn:$ip:3478?transport=tcp", "turn:$ip:3478?transport=udp"), //turn/stun uris
    'ttl'                   => 86400 //time to live in secs for provided temporary credentials
);
