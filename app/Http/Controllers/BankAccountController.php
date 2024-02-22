<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransferHistoryResource;
use App\Models\BankAccount;
use App\Models\TransferHistory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BankAccountController extends Controller
{
    private function createBankAccount()
    {
        $str_account = '';
        for ($i = 0; $i < 10; $i++) {
            $num = rand(0, 9);
            $str_account = $str_account . strval($num);
        }
        $account_number = intval($str_account);

        return $account_number;
    }

    private function accountDetails($id, $account_holder, $account_number, $email, $account_balance, $created_at)
    {
        $account_details =  [
            'id' => $id,
            'Account Holder' => $account_holder,
            'Account Number' => $account_number,
            'Email' => $email,
            'Account Balance' => $account_balance,
            'Created At' => $created_at,
        ];
        return $account_details;
    }
    public function store(Request $request)
    {

        $details = $request->validate([
            "account holder" => ["required", "string"],
            "email" => ["required", "string", 'email'],
            "account type" => ["required", "string"],

        ]);
        /*
        * Create bank account number
        */
        $account_number = $this->createBankAccount();

        /*
        * Check if account number already exist. If yes create new  account number
        */

        if (BankAccount::where('account_number', $account_number)->first()) {
            $account_number = $this->createBankAccount();
        }

        $account = BankAccount::create([
            'account_number' => $account_number,
            'account_holder' => $details['account holder'],
            'account_type' => $details['account type'],
            'email' => $details['email'],
        ]);
        return response()->json([
            'account_details' => $this->accountDetails($account->id, $account->account_holder, $account->account_number, $account->email, $account->account_balance, $account->created_at),
        ], 200);
    }

    public function updateBalance(Request $request): Response
    {
        $details = $request->validate([
            'account_holder' => ['required', 'string'],
            'account_number' => ['required'],
            'account_balance' => ['required']
        ]);
        $account_number = $details['account_number'];
        $account_balance = $details['account_balance'];
        $account = BankAccount::where('account_number', $account_number)->first();
        $account->update(['account_balance' => intval($account_balance)]);
        return response()->json([
            'account_details' => $this->accountDetails($account->id, $account->account_holder, $account->account_number, $account->email, $account->account_balance, $account->created_at)
        ], 200);
    }

    public function transfer(Request $request): Response
    {
        /*
        *Verify transfer details
        */
        $details = $request->validate([
            'id' => ['required', 'string'],
            'receivers_name' => ['required', 'string'],
            'receivers_account_number' => ['required'],
            'amount' => ['required']
        ]);
        $amount = intval($details['amount']);
        $sender = BankAccount::find($details['id']);
        $receiver = BankAccount::where('account_number', $details['receivers_account_number']);
        /*
        * Check if receiver's account is valid
        */
        if (!$receiver) {
            return response()->json([
                'message' => 'wrong credentials',
            ], 406);
        }
        $receivers_balance = $receiver->account_balance;
        $senders_balance = $sender->account_balance;
        /*
        * Check if sender has sufficient funds
        */
        if ($senders_balance - $amount <= 0) {
            return response()->json([
                'message' => 'insufficient funds'
            ], Response::HTTP_NOT_ACCEPTABLE);
        }
        $receivers_current_balance = $receivers_balance + $amount;
        $senders_current_balance = $senders_balance - $amount;
        /*
    * Update sender's and receiver's account to reflect current balance
    */
        $sender->update(['account_balance' => $senders_current_balance]);
        $receiver->update(['account_balance' => $receivers_current_balance]);
        /*
    * Create transfer history for sender
    */
        TransferHistory::create([
            'amount' => $amount,
            'receivers_name' => $details['receivers_name'],
            'receivers_account_number' => $details['receivers_account_number'],
            'bank_account_id' => $details['id'],
        ]);
        return response()->json([
            'message' => 'transfer successful'
        ], 200);
    }

    public function retrieveBalance(Request $request)
    {

        $details = $request->validate([
            'id' => ['required', 'string'],
        ]);
        $customer = BankAccount::find($details['id']);
        $balance = $customer->balance;
        return response()->json([
            'account_balance' => $balance,
        ], 200);
    }

    public function retrieveHistory(Request $request): Response
    {
        $transfer_history = TransferHistoryResource::collection(TransferHistory::where('bank_account_id', $request->id));

        return response()->json([
            'transfer_history' => $transfer_history
        ], 200);
    }
}
