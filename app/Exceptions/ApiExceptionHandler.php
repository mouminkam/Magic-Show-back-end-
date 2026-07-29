<?php

namespace App\Exceptions;

use App\Helpers\ApiResponseHelper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Handle exceptions for API requests.
     *
     * @param \Throwable $e
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function handle(Throwable $e)
    {
        // ValidationException - 422
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        // AuthenticationException - 401
        if ($e instanceof AuthenticationException) {
            return $this->handleAuthenticationException($e);
        }

        // AuthorizationException - 403
        if ($e instanceof AuthorizationException) {
            return $this->handleAuthorizationException($e);
        }

        // ModelNotFoundException - 404
        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($e);
        }

        // NotFoundHttpException - 404
        if ($e instanceof NotFoundHttpException) {
            return $this->handleNotFoundHttpException($e);
        }

        // MethodNotAllowedHttpException - 405
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->handleMethodNotAllowedException($e);
        }

        // ThrottleRequestsException - 429
        if ($e instanceof ThrottleRequestsException) {
            return $this->handleThrottleException($e);
        }

        // QueryException - 500
        if ($e instanceof QueryException) {
            return $this->handleQueryException($e);
        }

        // Generic Exception - 500
        return $this->handleGenericException($e);
    }

    /**
     * Handle validation exception.
     *
     * @param \Illuminate\Validation\ValidationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleValidationException(ValidationException $e)
    {
        return ApiResponseHelper::validationError(
            $e->errors(),
            __('errors.validation_failed')
        );
    }

    /**
     * Handle authentication exception.
     *
     * @param \Illuminate\Auth\AuthenticationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleAuthenticationException(AuthenticationException $e)
    {
        return ApiResponseHelper::unauthorized(
            $e->getMessage() ?: __('errors.unauthorized')
        );
    }

    /**
     * Handle authorization exception.
     *
     * @param \Illuminate\Auth\Access\AuthorizationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleAuthorizationException(AuthorizationException $e)
    {
        return ApiResponseHelper::forbidden(
            $e->getMessage() ?: __('errors.forbidden')
        );
    }

    /**
     * Handle model not found exception.
     *
     * @param \Illuminate\Database\Eloquent\ModelNotFoundException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e)
    {
        $modelName = class_basename($e->getModel());
        
        // Try to translate model name to user-friendly message
        $message = match($modelName) {
            'Product' => __('errors.product_not_found'),
            'Order' => __('errors.order_not_found'),
            default => __('errors.not_found')
        };

        return ApiResponseHelper::notFound($message);
    }

    /**
     * Handle not found HTTP exception.
     *
     * @param \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNotFoundHttpException(NotFoundHttpException $e)
    {
        return ApiResponseHelper::notFound(__('errors.not_found'));
    }

    /**
     * Handle method not allowed exception.
     *
     * @param \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleMethodNotAllowedException(MethodNotAllowedHttpException $e)
    {
        return ApiResponseHelper::error(
            'METHOD_NOT_ALLOWED',
            'The HTTP method is not allowed for this route.',
            405
        );
    }

    /**
     * Handle throttle exception.
     *
     * @param \Illuminate\Http\Exceptions\ThrottleRequestsException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleThrottleException(ThrottleRequestsException $e)
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
        
        return ApiResponseHelper::error(
            'TOO_MANY_REQUESTS',
            __('errors.too_many_requests'),
            429,
            $retryAfter ? ['retry_after' => $retryAfter] : null
        );
    }

    /**
     * Handle query exception.
     *
     * @param \Illuminate\Database\QueryException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleQueryException(QueryException $e)
    {
        // In production, don't expose SQL details
        if (config('app.debug')) {
            return ApiResponseHelper::error(
                'DATABASE_ERROR',
                'Database error: ' . $e->getMessage(),
                500
            );
        }

        return ApiResponseHelper::error(
            'DATABASE_ERROR',
            __('errors.server_error'),
            500
        );
    }

    /**
     * Handle generic exception.
     *
     * @param \Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleGenericException(Throwable $e)
    {
        // In production, don't expose exception details
        if (config('app.debug')) {
            return ApiResponseHelper::error(
                'SERVER_ERROR',
                'Server error: ' . $e->getMessage(),
                500,
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }

        return ApiResponseHelper::error(
            'SERVER_ERROR',
            __('errors.server_error'),
            500
        );
    }
}
