<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use App\Models\TransferHistory;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\TransferHistoryResource;

use App\Models\User;


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

    private function accountDetails($id, $account_holder, $account_number, $email, $account_balance, $account_type, $created_at)
    {
        $account_details =  [
            'id' => $id,
            'Account Holder' => $account_holder,
            'Account Number' => $account_number,
            'Account Type' => $account_type,
            'Email' => $email,
            'Account Balance' => $account_balance,
            'Created At' => $created_at,
        ];
        return $account_details;
    }
    public function store(Request $request)
    {

        $details = $request->validate([
            'id' => ['required', 'string'],
            "account_holder" => ["required", "string"],
            "email" => ["required", "string", 'email'],
            "account_type" => ["required", "string"],

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
        /*
        * check if user already have an account of same type
        */
        $account_exist = BankAccount::where([
            ['account_holder', '=', $details['account_holder']],
            ['account_type', '=', $details['account_type']],
        ])->get();
        if (count($account_exist) > 0) {
            return response()->json([
                'message' => 'Account holder already has an account of same type',

            ], Response::HTTP_NOT_ACCEPTABLE);
        }
        /*
        * Get the user creating the account 
        */
        $user = User::find($request->id);
        /*
        * populate db 
        */
        if ($user->isAdmin) {
            $account = BankAccount::create([
                'account_number' => $account_number,
                'account_holder' => $details['account_holder'],
                'account_type' => $details['account_type'],
                'email' => $details['email'],
            ]);
        } else {
            $account = BankAccount::create([
                'account_number' => $account_number,
                'account_holder' => $details['account_holder'],
                'account_type' => $details['account_type'],
                'email' => $details['email'],
                'user_id' => $request->id,
            ]);
        }
        return response()->json([
            'account_details' => $this->accountDetails($account->id, $account->account_holder, $account->account_number, $account->email, $account->account_balance, $account->account_type, $account->created_at),
        ], 200);
    }

    public function updateBalance(Request $request): Response
    {
        $id = $request->id;
        $user = User::find($id);
        if (!$user || !$user->isAdmin) {
            return response()->json([
                'error' => 'unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }
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
            'account_details' => $this->accountDetails($account->id, $account->account_holder, $account->account_number, $account->email, $account->account_balance, $account->account_type, $account->created_at)
        ], 200);
    }

    public function transfer(Request $request): Response
    {
        $id = $request->id;
        $user = User::find($id);
        /*
        *   Check if request is from teller or customer
        */
        if ($user->isAdmin) {
            /*
        *Verify transfer details
        */
            $details = $request->validate([
                'id' => ['required', 'string'],
                'receivers_name' => ['required', 'string'],
                'receivers_account_number' => ['required'],
                'senders_name' => ['required', 'string'],
                'senders_account_number' => ['required', 'string'],
                'amount' => ['required']
            ]);
        } else {
            /*
        *Verify transfer details
        */
            $details = $request->validate([
                'id' => ['required', 'string'],
                'receivers_name' => ['required', 'string'],
                'receivers_account_number' => ['required'],
                'senders_account_number' => ['required', 'string'],
                'amount' => ['required']
            ]);
        }
        $sender = BankAccount::where('account_number', $details['senders_account_number'])->first();
        $amount = intval($details['amount']);

        $receiver = BankAccount::where('account_number', $details['receivers_account_number'])->first();
        /*
        * Check if receiver's account is valid
        */
        if (!$receiver) {
            return response()->json([
                'message' => 'wrong credentials',
            ], 406);
        }
        if ($details['senders_account_number'] === $details['receivers_account_number']) {
            return response()->json([
                'message' => "sender's account number can't be same with receiver's account number"
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


        $receivers_new_balance = $receivers_balance + $amount;
        $senders_new_balance = $senders_balance - $amount;
        /*
    * Update sender's and receiver's account to reflect new balance
    */

        $sender->update(['account_balance' => $senders_new_balance]);
        $receiver->update(['account_balance' => $receivers_new_balance]);
        /*
    * Create transfer history for sender
    */
        TransferHistory::create([
            'amount' => $amount,
            'receivers_name' => $details['receivers_name'],
            'receivers_account_number' => $details['receivers_account_number'],
            'bank_account_id' => $sender->id,
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
        $transfer_history = TransferHistoryResource::collection(TransferHistory::where('bank_account_id', $request->id)->get());

        return response()->json([
            'transfer_history' => $transfer_history
        ], 200);
    }
}
