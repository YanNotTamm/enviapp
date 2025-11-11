<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

/**
 * Production Exception Handler
 * 
 * This handler provides secure error responses in production environment
 * by hiding sensitive information and providing user-friendly error messages.
 */
class ProductionExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * Determines the HTTP status code to use based on the exception.
     */
    public function determineStatusCode(Throwable $exception, ?int $statusCode = null): int
    {
        // If status code is already set, use it
        if ($statusCode !== null) {
            return $statusCode;
        }

        // Check if exception has a status code
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        // Default to 500 for unhandled exceptions
        return 500;
    }

    /**
     * Determines the view file to use based on the exception.
     */
    public function determineView(Throwable $exception, string $templatePath, ?int $statusCode = null): string
    {
        $statusCode = $this->determineStatusCode($exception, $statusCode);
        
        // Use status code specific view if available
        $viewPath = $templatePath . '/production/' . $statusCode . '.php';
        
        if (is_file($viewPath)) {
            return 'production/' . $statusCode;
        }

        // Fall back to generic error view
        return 'production/error';
    }

    /**
     * Handle the exception and generate appropriate response.
     */
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode
    ): void {
        // Log the exception with full details
        $this->logException($exception, $statusCode);

        // Determine if this is an API request
        $isApiRequest = $this->isApiRequest($request);

        if ($isApiRequest) {
            $this->handleApiError($exception, $response, $statusCode);
        } else {
            $this->handleWebError($exception, $response, $statusCode);
        }
    }

    /**
     * Check if the request is an API request.
     */
    protected function isApiRequest(RequestInterface $request): bool
    {
        // Check if request path starts with /api
        $uri = $request->getUri();
        $path = $uri->getPath();
        
        if (strpos($path, '/api') === 0) {
            return true;
        }

        // Check Accept header
        $accept = $request->getHeaderLine('Accept');
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        // Check if request is AJAX
        if ($request->isAJAX()) {
            return true;
        }

        return false;
    }

    /**
     * Handle API error responses.
     */
    protected function handleApiError(Throwable $exception, ResponseInterface $response, int $statusCode): void
    {
        // Prepare safe error message
        $message = $this->getSafeErrorMessage($exception, $statusCode);
        
        // Prepare error response
        $errorResponse = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ];

        // Add error code if available
        if (method_exists($exception, 'getCode') && $exception->getCode()) {
            $errorResponse['error_code'] = $exception->getCode();
        }

        // Set response
        $response->setStatusCode($statusCode);
        $response->setJSON($errorResponse);
        $response->send();
    }

    /**
     * Handle web error responses.
     */
    protected function handleWebError(Throwable $exception, ResponseInterface $response, int $statusCode): void
    {
        $message = $this->getSafeErrorMessage($exception, $statusCode);
        
        // Simple HTML error page
        $html = $this->generateErrorPage($statusCode, $message);
        
        $response->setStatusCode($statusCode);
        $response->setBody($html);
        $response->send();
    }

    /**
     * Get a safe error message that doesn't expose sensitive information.
     */
    protected function getSafeErrorMessage(Throwable $exception, int $statusCode): string
    {
        // Map status codes to user-friendly messages
        $messages = [
            400 => 'Bad Request. Please check your input and try again.',
            401 => 'Authentication required. Please log in to continue.',
            403 => 'Access denied. You do not have permission to access this resource.',
            404 => 'The requested resource was not found.',
            405 => 'Method not allowed.',
            422 => 'Validation failed. Please check your input.',
            429 => 'Too many requests. Please try again later.',
            500 => 'An internal server error occurred. Please try again later.',
            502 => 'Bad Gateway. The server is temporarily unavailable.',
            503 => 'Service temporarily unavailable. Please try again later.',
        ];

        // Return predefined message or generic error
        return $messages[$statusCode] ?? 'An error occurred while processing your request.';
    }

    /**
     * Log the exception with full details.
     */
    protected function logException(Throwable $exception, int $statusCode): void
    {
        $logger = Services::logger();
        
        // Prepare log message
        $message = sprintf(
            'Exception: %s | Message: %s | File: %s | Line: %d | Status: %d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $statusCode
        );

        // Log based on status code severity
        if ($statusCode >= 500) {
            $logger->error($message, ['exception' => $exception]);
        } elseif ($statusCode >= 400) {
            $logger->warning($message);
        } else {
            $logger->info($message);
        }
    }

    /**
     * Generate a simple HTML error page.
     */
    protected function generateErrorPage(int $statusCode, string $message): string
    {
        $title = $statusCode . ' Error';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            margin: 0;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin: 20px 0;
        }
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin: 20px 0;
        }
        .back-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$statusCode}</div>
        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$message}</p>
        <a href="/" class="back-button">Go to Homepage</a>
    </div>
</body>
</html>
HTML;
    }
}
