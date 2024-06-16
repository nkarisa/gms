<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class SessionCheck implements FilterInterface
{
/**
 * This method is called before the request is processed by the controller.
 * It checks if the user is authenticated by checking the session.
 * If the user is not authenticated, it redirects to the login page.
 *
 * @param RequestInterface $request The current request object.
 * @param mixed $arguments Additional arguments passed to the filter.
 *
 * @return mixed|ResponseInterface|null Returns a ResponseInterface object if a redirect is needed, null otherwise.
 */
public function before(RequestInterface $request, $arguments = null)
{
    $session = \Config\Services::session();

    // Check if the user is authenticated
    if (!$session->has('user_is_authenticated')) {
        // Redirect to the login page if the user is not authenticated
        return redirect()->to('/login/index');
    }
}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here if needed
    }
}
