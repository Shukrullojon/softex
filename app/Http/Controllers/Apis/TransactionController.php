<?php

namespace App\Http\Controllers\Apis;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/transactions",
     *     summary="Get all transactions for the authenticated user",
     *     tags={"Transaction"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of transactions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $transactions = Transaction::where('user_id', $user->id)->get();
        return response()->json([
            'status' => true,
            'transactions' => TransactionResource::collection($transactions)
        ], 200);
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @OA\Post(
     *     path="/transactions",
     *     summary="Create a new transaction",
     *     tags={"Transaction"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date", "amount", "type", "category_id"},
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="amount", type="number", format="double"),
     *             @OA\Property(property="type", type="integer", enum={1, 2}),
     *             @OA\Property(property="category_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $validated = Validator::make($request->all(), [
            'date' => 'required|date|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'type' => 'required|in:1,2',
            'category_id' => 'required|exists:categories,id',
        ]);
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validated error',
                'erors' => $validated->errors()
            ], 401);
        }
        $transaction = Transaction::create([
            'date' => $request->date,
            'amount' => ($request->type == 1) ? $request->amount : $request->amount * -1,
            'type' => $request->type,
            'user_id' => $user->id,
            'category_id' => $request->category_id,
        ]);
        $user->update([
            'balans' => $user->balans + $transaction->amount,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Transaction created successfully',
            'transaction' => new TransactionResource($transaction)
        ], 201);
    }

    /**
     * Display the specified transaction.
     *
     * @OA\Get(
     *     path="/transactions/{id}",
     *     summary="Show a specific transaction",
     *     tags={"Transaction"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction details",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->first();
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
        return response()->json([
            'status' => true,
            'transaction' => new TransactionResource($transaction)
        ], 200);
    }

    /**
     * Update the specified transaction in storage.
     *
     * @OA\Put(
     *     path="/transactions/{id}",
     *     summary="Update an existing transaction",
     *     tags={"Transaction"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date", "amount", "type", "category_id"},
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="amount", type="number", format="double"),
     *             @OA\Property(property="type", type="integer", enum={1, 2}),
     *             @OA\Property(property="category_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $validated = Validator::make($request->all(), [
            'date' => 'required|date|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'type' => 'required|in:1,2',
            'category_id' => 'required|exists:categories,id',
        ]);
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validated error',
                'erors' => $validated->errors()
            ], 401);
        }
        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->first();
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
        $user->update([
            'balans' => $user->balans + $transaction->amount * -1,
        ]);
        $transaction->update([
            'date' => $request->date,
            'amount' => $request->type == 1 ? $request->amount : $request->amount * -1,
            'type' => $request->type,
            'category_id' => $request->category_id,
        ]);
        $user->update([
            'balans' => $user->balans + $transaction->amount,
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Transaction updated successfully',
            'transaction' => new TransactionResource($transaction)
        ], 200);
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @OA\Delete(
     *     path="/transactions/{id}",
     *     summary="Delete a transaction",
     *     tags={"Transaction"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->first();
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
        $user->update([
            'balans' => $user->balans + $transaction->amount * -1,
        ]);
        $transaction->delete();
        return response()->json([
            'status' => true,
            'message' => 'Transaction deleted successfully'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/transactions/statistics",
     *     summary="Get transaction statistics for the authenticated user",
     *     tags={"Transactions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="date",
     *             example="2024-01-01"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="date",
     *             example="2024-01-31"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(
     *                 property="statistics",
     *                 type="object",
     *                 @OA\Property(property="type", type="integer"),
     *                 @OA\Property(property="amount", type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function getStatistics(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $st = Transaction::select("type", DB::raw("sum(amount) as amount"))
            ->where('user_id',$user->id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->groupBy("type")
            ->get();
        return response()->json([
            'status' => true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'statistics' => $st,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/transactions/export",
     *     summary="Export transactions to Excel",
     *     tags={"Transactions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2023-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2023-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel file containing transactions",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function exportTransactions(Request $request): BinaryFileResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $export = new TransactionsExport($request->start_date, $request->end_date, $user->id);
        return Excel::download($export, time().rand(100,999).'.xlsx');
    }
}
