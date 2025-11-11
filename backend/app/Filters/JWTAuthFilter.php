<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTAuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Authorization header required'
                ]);
        }
        
        // Extract token from Bearer
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid authorization header format'
                ]);
        }
        
        $token = $matches[1];
        $jwtSecret = getenv('JWT_SECRET');
        
        if (!$jwtSecret) {
            return service('response')
                ->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'JWT_SECRET not configured'
                ]);
        }
        
        try {
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            
            // Check if token has expired (additional check)
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Token has expired'
                    ]);
            }
            
            // Check role-based access if arguments provided
            if ($arguments && isset($decoded->role)) {
                $requiredRoles = is_array($arguments) ? $arguments : [$arguments];
                if (!in_array($decoded->role, $requiredRoles)) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => 'error',
                            'message' => 'Insufficient permissions'
                        ]);
                }
            }
            
            // Add user data to request for use in controllers
            $request->user = $decoded;
            
        } catch (ExpiredException $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Token has expired'
                ]);
        } catch (SignatureInvalidException $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid token signature'
                ]);
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid token: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an exception or raising an error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}