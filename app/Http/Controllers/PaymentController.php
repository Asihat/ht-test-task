<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\MoneyTransfer;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addMoneyToBalance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|gt:0',
            'user_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user_id = $request->user_id;
        $amount = $request->amount;

        try {
            DB::beginTransaction();
            $account = Account::where('user_id', $user_id)->firstOrCreate([
                'user_id' => $user_id
            ]);
            $account->total_balance += $amount;
            $account->save();

            $transaction = PaymentTransaction::create([
                'user_id' => $user_id,
                'amount' => $amount
            ]);
            DB::commit();

            return response()->json([
                "message" => "ADD MONEY TO BALANCE",
                "account" => $account,
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Database transaction failed: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function subMoneyFromBalance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|gt:0',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user_id = $request->user_id;
        $amount = $request->amount;

        $account = Account::firstOrCreate(
            ['user_id' => $user_id]
        );

        if ($account->total_balance < $amount) {
            $account->total_balance -= $amount;
            return response()->json([
                'message' => 'not enough money',
                'success' => false
            ], Response::HTTP_BAD_REQUEST);
        }
        $account->total_balance -= $amount;
        $account->save();

        return response()->json([
            "message" => "SUB MONEY FROM BALANCE",
            'account' => $account
        ]);
    }

    public function transferMoneyToUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|gt:0',
            'sender_id' => 'required|integer',
            'getter_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sender_id = $request->sender_id;
        $getter_id = $request->getter_id;
        $amount = $request->amount;

        $sender = Account::where('user_id', $sender_id)->firstOrFail();
        $getter = Account::where('user_id', $getter_id)->firstOrFail();

        try {
            DB::beginTransaction();
            if ($sender->total_balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Not enough money',
                    'success' => false
                ], Response::HTTP_BAD_REQUEST);
            }
            $sender->total_balance -= $amount;
            $getter->total_balance += $amount;

            $sender->save();
            $getter->save();

            $transfer = MoneyTransfer::create([
                'sender_id' => $sender_id,
                'getter_id' => $getter_id,
                'amount' => $amount
            ]);

            DB::commit();

            return response()->json([
                "message" => "TRANSFER MONEY TO USER",
                'getter' => $getter,
                'sender' => $sender,
                'transfer' => $transfer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Database transaction failed: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function getBalance(Request $request, $id): JsonResponse
    {
        $cacheKey = 'account_' . $id;
        $account = Cache::remember($cacheKey, 60, function () use ($id) {
            try {
                return Account::where('user_id', $id)->firstOrFail();
            } catch (ModelNotFoundException $exception) {
                return null;
            }
        });

        if (!$account) {
            return response()->json([
                'error' => 'Account not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            "message" => "GET BALANCE",
            "account" => $account,
        ]);
    }
}
