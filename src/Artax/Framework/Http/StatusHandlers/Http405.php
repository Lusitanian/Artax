<?php
/**
 * Http405 Handler Class File
 * 
 * @category     Artax
 * @package      Framework
 * @subpackage   Http
 * @author       Daniel Lowrey <rdlowrey@gmail.com>
 * @license      All code subject to the terms of the LICENSE file in the project root
 * @version      ${project.version}
 */
namespace Artax\Framework\Http\StatusHandlers;

use Artax\Events\Mediator,
    Artax\Http\Request,
    Artax\Http\Response,
    Artax\Framework\Http\Exceptions\MethodNotAllowedException;

/**
 * A default handler for 405 scenarios
 * 
 * @category     Artax
 * @package      Framework
 * @subpackage   Http
 * @author       Daniel Lowrey <rdlowrey@gmail.com>
 */
class Http405 {
    
    /**
     * @var Mediator
     */
    private $mediator;
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var Response
     */
    private $response;
    
    /**
     * @param Mediator $mediator
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function __construct(Mediator $mediator, Request $request, Response $response) {
        $this->mediator = $mediator;
        $this->request  = $request;
        $this->response = $response;
    }
    
    /**
     * @return void
     */
    public function __invoke(MethodNotAllowedException $e) {
        $this->response->setStatusCode(405);
        $this->response->setStatusDescription('Method Not Allowed');
        
        // As per http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7 ...
        // An Allow header field MUST be present in a 405 (Method Not Allowed) response
        $this->response->setHeader(
            'Allow',
            strtoupper(implode(',', $e->getAvailableResourceMethods()))
        );
        
        if (!$this->mediator->notify('app.http-405', $this->request, $this->response, $e)) {
            $body = '<html>'. PHP_EOL .'<body>' . PHP_EOL;
            $body .= '<h1>405 Method Not Allowed</h1>' . PHP_EOL . '<hr />' . PHP_EOL;
            $body .= '<p>Request Method: <em>'.$this->request->getMethod().'</em></p>';
            $body .= '<body>'. PHP_EOL .'</html>';
            
            $this->response->setBody($body);
            $this->response->setHeader('Content-Type', 'text/html');
            $this->response->setHeader('Content-Length', strlen($body));
        }
        
        if (!$this->response->wasSent()) {
            $this->response->send();
        }
        
        return false;
    }
}
