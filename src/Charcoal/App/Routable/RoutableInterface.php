<?php

namespace Charcoal\App\Routable;

// PSR-7 (http messaging) dependencies
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Routable objects are loadable from a URL.
 */
interface RoutableInterface
{
    /**
     * @param boolean $routable Routable flag, if the object is routable or not.
     * @return RoutableInterface Chainable
     */
    public function setRoutable($routable);

    /**
     * @return boolean
     */
    public function routable();

    /**
     * @param mixed $pattern The slug / URL / route pattern (translation string).
     * @return RoutableInterface Chainable
     */
    public function setSlugPattern($pattern);

    /**
     * @return string
     */
    public function slugPattern();

    /**
     * @param mixed $slug The slug value (translation string).
     * @return RoutableInterface Chainable
     */
    public function setSlug($slug);

    /**
     * @return string
     */
    public function slug();

    /**
     * Generate a URL slug from the object's URL slug pattern.
     *
     * @return string
     */
    public function generateSlug();

    /**
     * @return string
     */
    public function url();

    /**
     * Get the route callback that matches a given path (or null).
     *
     * @param string            $path     The URL path to load.
     * @param RequestInterface  $request  The PSR-7 compatible Request instance.
     * @param ResponseInterface $response The PSR-7 compatible Response instance.
     * @return callable|null Route callable
     */
    public function routeHandler($path, RequestInterface $request, ResponseInterface $response);
}