<?php

namespace App\Helpers;

class ApiResponseHelper
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param array|null $meta
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, $message = null, $meta = null, $statusCode = 200)
    {
        $response = ['success' => true];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($meta !== null) {
            $response['meta'] = $meta;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Return a created (201) JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function created($data, $message = null)
    {
        return self::success($data, $message, null, 201);
    }
    
    /**
     * Return a no content (204) response.
     *
     * @return \Illuminate\Http\Response
     */
    public static function noContent()
    {
        return response()->noContent();
    }
    
    /**
     * Return an error JSON response.
     *
     * @param string $code
     * @param string $message
     * @param int $statusCode
     * @param mixed $details
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($code, $message, $statusCode = 400, $details = null)
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Return a validation error (422) response.
     *
     * @param array $errors
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function validationError($errors, $message = null)
    {
        return self::error(
            'VALIDATION_ERROR',
            $message ?? __('errors.validation_failed'),
            422,
            $errors
        );
    }
    
    /**
     * Return an unauthorized (401) response.
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function unauthorized($message = null)
    {
        return self::error(
            'AUTH_REQUIRED',
            $message ?? __('errors.unauthorized'),
            401
        );
    }
    
    /**
     * Return a forbidden (403) response.
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function forbidden($message = null)
    {
        return self::error(
            'FORBIDDEN',
            $message ?? __('errors.forbidden'),
            403
        );
    }
    
    /**
     * Return a not found (404) response.
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function notFound($message = null)
    {
        return self::error(
            'NOT_FOUND',
            $message ?? __('errors.not_found'),
            404
        );
    }
    
    /**
     * Return a paginated JSON response.
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $items
     * @param array $additionalData
     * @return \Illuminate\Http\JsonResponse
     */
    public static function paginated($items, $additionalData = [])
    {
        $pagination = [
            'currentPage' => $items->currentPage(),
            'limit' => $items->perPage(),
            'totalItems' => $items->total(),
            'totalPages' => $items->lastPage(),
            'hasNext' => $items->hasMorePages(),
            'hasPrev' => $items->currentPage() > 1
        ];
        
        return self::success(
            array_merge(['items' => $items->items()], $additionalData),
            null,
            ['pagination' => $pagination]
        );
    }
}
