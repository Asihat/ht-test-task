<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testAddMoneyToBalance()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        // Prepare the request data
        $requestData = [
            'user_id' => $user->id,
            'amount' => 100
        ];

        $response = $this->postJson('/api/add', $requestData);

        // Assert the response
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'ADD MONEY TO BALANCE',
            ]);

        $updatedAccount = Account::find($account->id);
        $this->assertEquals($account->total_balance + $requestData['amount'], $updatedAccount->total_balance);

        // Assert that a payment transaction was created
        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'amount' => $requestData['amount'],
        ]);
    }

    public function testSubMoneyFromBalance(): void
    {
        $account = Account::factory()->create([
            'user_id' => 123,
            'total_balance' => 1000,
        ]);

        $requestData = [
            'user_id' => 123,
            'amount' => 500,
        ];

        $response = $this->postJson('/api/sub', $requestData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'SUB MONEY FROM BALANCE',
                'account' => [
                    'user_id' => 123,
                    'total_balance' => 500,
                ],
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => 123,
            'total_balance' => 500,
        ]);
    }

    public function testTransferMoneyToUser(): void
    {
        $sender = Account::factory()->create([
            'user_id' => 123,
            'total_balance' => 1000,
        ]);
        $getter = Account::factory()->create([
            'user_id' => 456,
            'total_balance' => 500,
        ]);

        $requestData = [
            'sender_id' => 123,
            'getter_id' => 456,
            'amount' => 500,
        ];

        $response = $this->postJson('/api/transfer', $requestData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'TRANSFER MONEY TO USER',
                'getter' => [
                    'user_id' => 456,
                    'total_balance' => 1000,
                ],
                'sender' => [
                    'user_id' => 123,
                    'total_balance' => 500,
                ],
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $sender->id,
            'user_id' => 123,
            'total_balance' => 500,
        ]);
        $this->assertDatabaseHas('accounts', [
            'id' => $getter->id,
            'user_id' => 456,
            'total_balance' => 1000,
        ]);

        $this->assertDatabaseHas('money_transfers', [
            'sender_id' => 123,
            'getter_id' => 456,
            'amount' => 500,
        ]);
    }

    public function testGetBalance(): void
    {
        $account = Account::factory()->create([
            'user_id' => 123,
            'total_balance' => 1000,
        ]);

        $response = $this->getJson('/api/balance/' . $account->user_id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'GET BALANCE',
                'account' => [
                    'user_id' => 123,
                    'total_balance' => 1000,
                ],
            ]);
    }

    public function testGetBalanceNotFound(): void
    {
        $response = $this->getJson('/api/balance/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'error' => 'Account not found',
            ]);
    }
}
