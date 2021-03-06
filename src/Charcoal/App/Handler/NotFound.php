<?php

namespace Charcoal\App\Handler;

// Dependencies from PSR-7 (HTTP Messaging)
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Dependency from Slim
use Slim\Http\Body;

// Local Dependencies
use Charcoal\App\Handler\AbstractHandler;

/**
 * Not Found Handler
 *
 * Enhanced version of {@see \Slim\Handlers\NotAllowed}.
 *
 * It outputs a simple message in either JSON, XML,
 * or HTML based on the Accept header.
 */
class NotFound extends AbstractHandler
{
    /**
     * Invoke "Not Found" Handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object.
     * @param  ResponseInterface      $response The most recent Response object.
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonOutput();
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlOutput();
                break;

            case 'text/html':
            default:
                $output = $this->renderHtmlOutput();
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(404)
                        ->withHeader('Content-Type', $contentType)
                        ->withBody($body);
    }

    /**
     * Render Plain/Text Error
     *
     * @return string
     */
    protected function renderPlainOutput()
    {
        $message = $this->translator()->translate('Not Found');

        return $this->render($message);
    }

    /**
     * Render JSON Error
     *
     * @return string
     */
    protected function renderJsonOutput()
    {
        $message = $this->translator()->translate('Not Found');

        return $this->render('{"message":"'.$message.'"}');
    }

    /**
     * Render XML Error
     *
     * @return string
     */
    protected function renderXmlOutput()
    {
        $message = $this->translator()->translate('Not Found');

        return $this->render('<root><message>'.$message.'</message></root>');
    }

    /**
     * Render title of error
     *
     * @return string
     */
    public function messageTitle()
    {
        return $this->translator()->translate('Page Not Found');
    }

    /**
     * Render body of HTML error
     *
     * @return string
     */
    public function renderHtmlMessage()
    {
        $title = $this->messageTitle();
        $link  = sprintf(
            '<a href="%1$s">%2$s</a>',
            $this->baseUrl(),
            $this->translator()->translate('Visit the Home Page')
        );
        $notice  = $this->translator()->translate('The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.');
        $message = '<h1>'.$title."</h1>\n\t\t<p>".$notice."</p>\n\t\t<p>".$link."</p>\n";

        return $message;
    }
}
