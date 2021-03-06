<?php
/**
 * StatusApplier Class File
 * 
 * @category    ArtaxPlugins
 * @author      Daniel Lowrey <rdlowrey@gmail.com>
 * @license     All code subject to the terms of the LICENSE file in the project root
 * @version     ${project.version}
 */
namespace ArtaxPlugins;

use Artax\Http\Response;

/**
 * -adds HTTP status code and description to outgoing responses if needed
 * 
 * Built-in Artax response implementations will throw a LogicException if a response is sent without
 * an HTTP status code assigned. The plugin listens for outbound responses and adds the default
 * 200 status code prior to sending if no status code has been assigned.
 * 
 * If no status description is assigned to match the response code, the plugin will apply the
 * default status message for the response's HTTP status code if possible. The list of built-in
 * status codes can be found in the Artax\Http\StatusCodes class.
 * 
 * @category    ArtaxPlugins
 * @author      Daniel Lowrey <rdlowrey@gmail.com>
 */
class ResponseStatus {
    
    public function __invoke(Response $response) {
        $this->setStatusCode($response);
        $this->setStatusDescription($response);
    }
    
    public function setStatusCode(Response $response) {
        if (!$response->getStatusCode()) {
            $response->setStatusCode(200);
        }
    }
    
    public function setStatusDescription(Response $response) {
        if (null === $response->getStatusDescription()) {
            $constantName = 'HTTP_' . $response->getStatusCode();
            if ($description = constant("Artax\\Http\\StatusCodes::$constantName")) {
                $response->setStatusDescription($description);
            }
        }
    }
}
