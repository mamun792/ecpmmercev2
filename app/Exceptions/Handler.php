<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class Handler extends ExceptionHandler
{
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'errors' => [
                    'id' => $exception->getMessage() ?: 'The requested resource was not found.'
                ],
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Route not found',
                'errors' => [
                    'message' => 'The requested route could not be found.',
                ],
            ], 404);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission denied',
                'errors' => [
                    'message' => 'You do not have permission to access this resource.',
                ],
            ], 403);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Method not allowed',
                'errors' => [
                    'message' => 'The requested method is not allowed for this endpoint.',
                ],
            ], 405);
        }

        if ($exception instanceof HttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'HTTP error',
                'errors' => [
                    'message' => $exception->getMessage(),
                ],
            ], $exception->getStatusCode());
        }

        if ($exception instanceof CouponException) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'errors' => [
                    'coupon' => $exception->getMessage(),
                    'details' => $exception->getDetails(),
                ],
            ], $exception->getCode() ?: 422);
        }


        if ($exception instanceof AccessDeniedHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied',
                'errors' => [
                    'message' => $exception->getMessage(),
                ],
            ], 403);
        }

        if ($exception instanceof BadRequestHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bad request',
                'errors' => [
                    'message' => $exception->getMessage(),
                ],
            ], 400);
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests',
                'errors' => [
                    'message' => $exception->getMessage(),
                ],
            ], 429);
        }

        if ($exception instanceof UnsupportedMediaTypeHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unsupported media type',
                'errors' => [
                    'message' => $exception->getMessage(),
                ],
            ], 415);
        }

        // Default Internal Server Error (500)
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, please try again later.',
            'errors' => [
                'message' => app()->isLocal() ? $exception->getMessage() : 'An unexpected error occurred.',
            ],
        ], 500);
    }

    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated',
            'errors' => [
                'message' => 'Authentication is required to access this resource.',
            ],
        ], 401);
    }

    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid JSON',
            'errors' => $exception->errors(),
        ], 422);
    }
}
