<?php

namespace Charcoal\App\Handler;

use Throwable;

// Dependencies from PSR-7 (HTTP Messaging)
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Dependency from Slim
use Slim\Http\Body;

// Dependency from Pimple
use Pimple\Container;

// Local Dependencies
use Charcoal\App\Handler\AbstractHandler;

/**
 * Error Handler for PHP 7+ Throwables
 *
 * Enhanced version of {@see \Slim\Handlers\PhpError}.
 *
 * It outputs the error message and diagnostic information in either
 * JSON, XML, or HTML based on the Accept header.
 */
class PhpError extends AbstractHandler
{
    /**
     * Whether to output the error's details.
     *
     * @var boolean
     */
    protected $displayErrorDetails;

    /**
     * The caught throwable.
     *
     * @var Throwable
     */
    protected $error;

    /**
     * Inject dependencies from a Pimple Container.
     *
     * ## Dependencies
     *
     * - `array $settings` — Slim's settings.
     *
     * @param  Container $container A dependencies container instance.
     * @return PhpError Chainable
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $displayDetails = $container['settings']['displayErrorDetails'];
        $this->setDisplayErrorDetails($displayDetails);

        return $this;
    }

    /**
     * Invoke Error Handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object.
     * @param  ResponseInterface      $response The most recent Response object.
     * @param  Throwable              $error    The caught Throwable object.
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Throwable $error)
    {
        $this->setError($error);
        $this->logger->error($error->getMessage());
        $this->logger->error($error->getFile().':'.$error->getLine());

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
                break;
        }

        $this->writeToErrorLog();

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
                ->withStatus(500)
                ->withHeader('Content-type', $contentType)
                ->withBody($body);
    }

    /**
     * Set whether to display details of the error or a generic message.
     *
     * @param  boolean $state Whether to display error details.
     * @return PhpError Chainable
     */
    protected function setDisplayErrorDetails($state)
    {
        $this->displayErrorDetails = (boolean)$state;

        return $this;
    }

    /**
     * Retrieves wether the the details of the error message should be displayed.
     *
     * @return boolean
     */
    public function displayErrorDetails()
    {
        return $this->displayErrorDetails;
    }

    /**
     * Set the caught error.
     *
     * @param  Throwable $error The caught Throwable object.
     * @return PhpError Chainable
     */
    protected function setError(Throwable $error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Retrieves the caught error.
     *
     * @return Throwable
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Write to the error log if displayErrorDetails is false
     *
     * @return void
     */
    protected function writeToErrorLog()
    {
        if ($this->displayErrorDetails()) {
            return;
        }

        $error = $this->error();

        $message  = $this->translator()->translate('Application Error').':'.PHP_EOL;
        $message .= $this->renderTextError($error);
        while ($error = $error->getPrevious()) {
            $message .= PHP_EOL.'Previous error:'.PHP_EOL;
            $message .= $this->renderTextError($error);
        }

        $message .= PHP_EOL.'View in rendered output by enabling the "displayErrorDetails" setting.'.PHP_EOL;

        error_log($message);
    }

    /**
     * Render error as Text.
     *
     * @param  Throwable $error The caught Throwable object.
     * @return string
     */
    protected function renderTextError(Throwable $error)
    {
        $code    = $error->getCode();
        $message = $error->getMessage();
        $file    = $error->getFile();
        $line    = $error->getLine();
        $trace   = $error->getTraceAsString();

        $text = sprintf('Type: %s'.PHP_EOL, get_class($error));

        if ($code) {
            $text .= sprintf('Code: %s'.PHP_EOL, $code);
        }

        if ($message) {
            $text .= sprintf('Message: %s'.PHP_EOL, htmlentities($message));
        }

        if ($file) {
            $text .= sprintf('File: %s'.PHP_EOL, $file);
        }

        if ($line) {
            $text .= sprintf('Line: %s'.PHP_EOL, $line);
        }

        if ($trace) {
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }

    /**
     * Render JSON Error
     *
     * @return string
     */
    protected function renderJsonOutput()
    {
        $error = $this->error();
        $json  = [
            'message' => $this->translator()->translate('Application Error'),
        ];

        if ($this->displayErrorDetails()) {
            $json['error'] = [];

            do {
                $json['error'][] = [
                    'type'    => get_class($error),
                    'code'    => $error->getCode(),
                    'message' => $error->getMessage(),
                    'file'    => $error->getFile(),
                    'line'    => $error->getLine(),
                    'trace'   => explode("\n", $error->getTraceAsString()),
                ];
            } while ($error = $error->getPrevious());
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * Render XML Error
     *
     * @return string
     */
    protected function renderXmlOutput()
    {
        $error = $this->error();
        $title = $this->messageTitle();

        $xml = "<error>\n  <message>".$title."</message>\n";
        if ($this->displayErrorDetails()) {
            do {
                $xml .= "  <error>\n";
                $xml .= '    <type>'.get_class($error)."</type>\n";
                $xml .= '    <code>'.$error->getCode()."</code>\n";
                $xml .= '    <message>'.$this->createCdataSection($error->getMessage())."</message>\n";
                $xml .= '    <file>'.$error->getFile()."</file>\n";
                $xml .= '    <line>'.$error->getLine()."</line>\n";
                $xml .= '    <trace>'.$this->createCdataSection($error->getTraceAsString())."</trace>\n";
                $xml .= "  </error>\n";
            } while ($error = $error->getPrevious());
        }
        $xml .= '</error>';

        return $xml;
    }

    /**
     * Returns a CDATA section with the given content.
     *
     * @param  string $content The Character-Data to mark.
     * @return string
     */
    private function createCdataSection($content)
    {
        return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
    }

    /**
     * Render error as HTML.
     *
     * @param  Throwable $error The caught Throwable object.
     * @return string
     */
    protected function renderHtmlError(Throwable $error)
    {
        $code    = $error->getCode();
        $message = $error->getMessage();
        $file    = $error->getFile();
        $line    = $error->getLine();
        $trace   = $error->getTraceAsString();

        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($error));

        if ($code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        if ($message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        if ($file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        if ($line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        if ($trace) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }

    /**
     * Render body of HTML error
     *
     * @return string
     */
    public function renderHtmlMessage()
    {
        $error = $this->error();

        if ($this->displayErrorDetails()) {
            $html  = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderHtmlError($error);

            while ($error = $error->getPrevious()) {
                $html .= '<h2>Previous Error</h2>';
                $html .= $this->renderHtmlError($error);
            }
        } else {
            $html = '<p>'.$this->translator()->translate('A website error has occurred. Sorry for the temporary inconvenience.').'</p>';
        }

        $title   = $this->messageTitle();
        $message = '<h1>'.$title."</h1>\n".$html."\n";

        return $message;
    }
}
