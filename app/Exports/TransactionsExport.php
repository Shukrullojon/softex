<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $start_date;
    protected $end_date;
    protected $user_id;

    public function __construct($start_date, $end_date, $user_id)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->user_id = $user_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Transaction::where('user_id', $this->user_id)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Amount',
            'Type',
            'User ID',
            'Category ID',
            'Created At',
            'Updated At',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            $transaction->date,
            $transaction->amount,
            $transaction->type,
            $transaction->user_id,
            $transaction->category_id,
            $transaction->created_at,
            $transaction->updated_at,
        ];
    }
}
