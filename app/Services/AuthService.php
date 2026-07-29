<?php

namespace App\Services;

use App\Exceptions\AuthServiceException;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @param array{first_name:string,last_name:string,email:string,phone:string,password:string} $data
     * @return array{customer:Customer, token:string}
     */
    public function register(array $data): array
    {
        $customer = Customer::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $token = $customer->createToken('auth_token')->plainTextToken;

        return [
            'customer' => $customer,
            'token' => $token,
        ];
    }

    /**
     * @param array{identifier:string,password:string} $data
     * @return array{customer:Customer, token:string}
     */
    public function login(array $data): array
    {
        $identifier = $data['identifier'];

        $customer = str_starts_with($identifier, '+')
            ? Customer::where('phone', $identifier)->first()
            : Customer::where('email', $identifier)->first();

        if (!$customer || !Hash::check($data['password'], $customer->password)) {
            throw new AuthServiceException(
                'AUTH_INVALID_CREDS',
                401,
                __('auth.invalid_credentials')
            );
        }

        if (!$customer->is_active) {
            throw new AuthServiceException(
                'AUTH_ACCOUNT_INACTIVE',
                403,
                __('auth.account_deactivated')
            );
        }

        $customer->updateLastLogin();

        $token = $customer->createToken('auth_token')->plainTextToken;

        return [
            'customer' => $customer,
            'token' => $token,
        ];
    }

    public function logout(Customer $customer): void
    {
        $customer->currentAccessToken()?->delete();
    }
}
