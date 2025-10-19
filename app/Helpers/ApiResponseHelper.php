<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiResponseHelper
{
    public static function successResponse(mixed $data, bool $pagination = false, string $message = '', $responseCode = 200): JsonResponse
    {
        if ($pagination) {
            return response()->json([
                'status' => true,
                'status_code' => $responseCode,
                'message' => $message,
                // 'code'    => $responseCode,
                'data' => $data,
                'meta'  => [
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                    'per_page'  => $data->perPage(),
                    'current_page' => $data->currentPage()
                ]
            ], $responseCode);
        } else {
            return response()->json([
                'status' => true,
                'status_code' => $responseCode,

                'message' => $message,
                // 'code'    => $responseCode,
                'data' => $data
            ], $responseCode);
        }
    }

    /**
     * error response
     */
    public static function errorResponse(string $message, $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'status_code' => $code,
            // 'code'   => $code,
            'message' => $message,
            'data' => []
        ], $code);
    }

    /**
     * Server error log
     *
     * @return JsonResponse
     */
    public static function serverError($e = []): JsonResponse
    {
        // if (env('APP_ENV') != 'local') {

        //     Log::channel('slack')->error('server Error', [$e]);
        // }
        info('server error', [$e]);
        return response()->json([
            'status' => false,
            // 'code'  => 500,
            'message'   => 'Something Went Wrong',
            'data' => [],
            // 'log'   => $e
        ], 500);
    }

    /**
     * Undocumented function
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(string $message, $errors): JsonResponse
    {
        return response()->json([
            'status' => false,
            // 'code'  => 422,
            'message' => $message,
            'error'     => $errors,
            'data' => []
        ], 422);
    }

    public static function sendSlackLog(string $errorFrom, $exception)
    {
        if (env('APP_ENV') != 'local') {

            // Log::channel('slack')->error($errorFrom, [
            //     'exception_message' => $exception?->getMessage(),
            //     'exception_code' => $exception?->getCode(),
            //     'exception_file' => $exception?->getFile(),
            //     'exception_line' => $exception?->getLine(),
            //     'exception_trace' => $exception?->getTraceAsString(),
            // ]);
        }
    }
}