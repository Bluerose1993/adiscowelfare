<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BenefitExpenseReportService
{
    public function query(array $filters = []): Builder
    {
        $requests = DB::table('benefit_requests as r')
            ->leftJoin('benefits as linked', 'linked.id', '=', 'r.resulting_benefit_id')
            ->join('staff as s', 's.id', '=', 'r.staff_id')
            ->join('benefit_types as t', 't.id', '=', 'r.benefit_type_id')
            ->where('r.status', 'paid')
            ->selectRaw("'request' as source, r.id as record_id, r.staff_id, s.staff_id as staff_code, s.full_name as staff_name, r.benefit_type_id, t.name as benefit_type, r.subject as title, COALESCE(r.approved_amount, linked.amount, r.requested_amount, 0) as amount, COALESCE(linked.payment_date, DATE(r.receipt_confirmed_at), DATE(r.updated_at), DATE(r.reviewed_at)) as expense_date");

        $directBenefits = DB::table('benefits as b')
            ->join('staff as s', 's.id', '=', 'b.staff_id')
            ->join('benefit_types as t', 't.id', '=', 'b.benefit_type_id')
            ->whereNull('b.deleted_at')
            ->where('b.status', 'paid')
            ->whereNotExists(function (Builder $query) {
                $query->selectRaw('1')->from('benefit_requests as r')
                    ->whereColumn('r.resulting_benefit_id', 'b.id')
                    ->where('r.status', 'paid');
            })
            ->selectRaw("'benefit' as source, b.id as record_id, b.staff_id, s.staff_id as staff_code, s.full_name as staff_name, b.benefit_type_id, t.name as benefit_type, b.title, b.amount, COALESCE(b.payment_date, DATE(b.updated_at), b.approved_date) as expense_date");

        $query = DB::query()->fromSub($requests->unionAll($directBenefits), 'expenses');

        if (! empty($filters['year'])) {
            $query->whereYear('expense_date', (int) $filters['year']);
        }
        if (! empty($filters['benefit_type_id'])) {
            $query->where('benefit_type_id', (int) $filters['benefit_type_id']);
        }
        if (! empty($filters['staff_id'])) {
            $query->where('staff_id', (int) $filters['staff_id']);
        }

        return $query;
    }
}
