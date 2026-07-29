<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Exceptions\AuthServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new customer
     */
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->authService->register($validated);
            $customer = $result['customer'];
            $token = $result['token'];

            return ApiResponseHelper::created([
                'customer' => new CustomerResource($customer),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], __('auth.registration_successful'));
            
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'REGISTRATION_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Login customer
     */
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->authService->login($validated);
            $customer = $result['customer'];
            $token = $result['token'];

            return ApiResponseHelper::success([
                'customer' => new CustomerResource($customer),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], __('auth.login_successful'));
        } catch (AuthServiceException $e) {
            return ApiResponseHelper::error(
                $e->getErrorCode(),
                $e->getMessage() ?: __('errors.server_error'),
                $e->getStatusCode()
            );
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'LOGIN_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Logout customer
     */
    public function logout(Request $request)
    {
        try {
            /** @var Customer $customer */
            $customer = $request->user();
            $this->authService->logout($customer);

            return ApiResponseHelper::success(
                null,
                __('auth.logout_successful')
            );
            
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'LOGOUT_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get authenticated customer
     */
    public function user(Request $request)
    {
        return ApiResponseHelper::success(
            new CustomerResource($request->user())
        );
    }

    /**
     * Send password reset link (forgot password)
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        // Security: never disclose whether an address is registered. Both
        // RESET_LINK_SENT and INVALID_USER return the identical response so the
        // endpoint cannot be used to enumerate customer accounts.
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return ApiResponseHelper::success(
                ['message' => __('passwords.sent')],
                __('passwords.sent'),
                null,
                200
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponseHelper::error(
                'PASSWORD_RESET_THROTTLED',
                __('passwords.throttled'),
                429
            );
        }

        return ApiResponseHelper::error(
            'PASSWORD_RESET_FAILED',
            __('passwords.user'),
            400
        );
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $customer->save();

                // Security: a password reset must invalidate every previously
                // issued API token, otherwise an attacker who stole a token
                // keeps access after the victim resets their password.
                $customer->tokens()->delete();

                event(new PasswordReset($customer));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponseHelper::success(
                ['message' => __('passwords.reset')],
                __('passwords.reset'),
                null,
                200
            );
        }

        return ApiResponseHelper::error(
            'PASSWORD_RESET_FAILED',
            __('passwords.token'),
            400
        );
    }

    /**
     * Update authenticated customer profile
     */
    public function updateProfile(ProfileUpdateRequest $request)
    {
        $customer = $request->user();
        $customer->update($request->validated());

        return ApiResponseHelper::success(
            new CustomerResource($customer->fresh()),
            __('messages.profile_updated'),
            null,
            200
        );
    }
}
