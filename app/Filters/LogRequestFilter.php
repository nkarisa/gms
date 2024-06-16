<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class LogRequestFilter implements FilterInterface
{
/**
 * This method is called before the request is processed.
 *
 * @param RequestInterface $request The current request object.
 * @param mixed $arguments Additional arguments provided to the filter.
 *
 * @return void
 */
public function before(RequestInterface $request, $arguments = null)
{
    // Log request details
    $log = [
        'userInfo' => !session()->has('user_id') ? [] : ['id' => session()->get('user_id'), 'roles' => session()->get('role_ids')],
        'server' => $request->getServer(),
    ];

    // Log the request details using the 'notice' log level and encode the data as JSON
    log_message('notice', json_encode($log));
}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after the request
    }
}
