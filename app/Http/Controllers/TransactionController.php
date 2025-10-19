<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest; 
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request) {
       
        $q = Transaction::with('category')
            ->where('user_id',$request->user()->id)
            ->when($request->query('type'), fn($qq,$type) => $qq->where('type',$type))
            ->when($request->query('month'), function($qq,$month) {
                // month format: YYYY-MM
                $qq->whereBetween('occurred_on', [
                    $month.'-01',
                    now()->parse($month.'-01')->endOfMonth()->toDateString()
                ]);
            })
            ->orderByDesc('occurred_on')
            ->orderByDesc('id');

        return $q->paginate($request->integer('per_page', 20));
    }

    public function store(StoreTransactionRequest $request) {

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $txn = Transaction::create($data);
        
         return ApiResponseHelper::successResponse($txn->load('category'), false, 'Transaction created successfully.', 201) ;
        
    }

    public function show(Request $request, Transaction $transaction) {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        return $transaction->load('category');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction) {
      
       // dd($transaction->user_id );
        abort_unless($transaction->user_id === $request->user()->id, 403);

        DB::beginTransaction();
        try {
            $validated = $request->validated();
            Log::info('txn.update payload', [
                'id'=>$transaction->id,
                'validated'=>$validated,
            ]);
            $transaction->update($validated);

            DB::commit();
            return $transaction->load('category');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('txn.update failed', ['id'=>$transaction->id, 'err'=>$e->getMessage()]);
            return response()->json(['message'=>'Failed to update transaction'], 500);
        }
    }
   





    public function destroy(Request $request, Transaction $transaction) {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        $transaction->delete();
        return response()->json(['deleted'=>true]);
    }
}
