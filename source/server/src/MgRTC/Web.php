<?php

namespace MgRTC;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

/**
 * Description of Web
 *
 * @author magnoliyan
 */
class Web implements HttpServerInterface {    

    public function onOpen( ConnectionInterface $conn, RequestInterface $request = null )
    {    
        $response = new Response( 200, ['Content-Type' => 'text/html; charset=utf-8'] );
        $response->setBody('Hello World!');
        $conn->send($response);
        $conn->close();
    }

    public function onClose( ConnectionInterface $conn )
    {
    }

    public function onError( ConnectionInterface $conn, \Exception $e )
    {
    }

    public function onMessage( ConnectionInterface $from, $msg )
    {
    }
}
