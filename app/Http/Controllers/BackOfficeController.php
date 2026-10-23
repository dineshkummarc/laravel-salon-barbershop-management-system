<?php

namespace App\Http\Controllers;

use App\Domain\Audit;
use App\Models\LedgerEntry;
use App\Models\Sale;
use App\Support\Modules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ImranDev\UnicodePdf\Facades\UnicodePdf;

class BackOfficeController extends Controller
{
    public function receipt(int $sale)
    {
        $record = Sale::with('items', 'payments', 'customer', 'branch', 'business')->findOrFail($sale);
        if (auth()->user()->role === 'customer') {
            abort_unless($record->customer?->user_id === auth()->id(), 403);
        } else {
            abort_unless(auth()->user()->allows('orders'), 403);
        }

        return view('site.receipt', ['sale' => $record, 'business' => $record->business]);
    }

    public function pdf(int $sale)
    {
        $response = $this->receipt($sale);
        $data = $response->getData();

        return UnicodePdf::preset('bengali')->loadView('site.invoice-pdf', $data + ['pdf' => true])->a4()->download($data['sale']->number.'.pdf');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->allows('reports'), 403);
        Modules::require('reports');
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        $start = Carbon::parse($data['from'], 'Asia/Dhaka')->startOfDay()->utc();
        $end = Carbon::parse($data['to'], 'Asia/Dhaka')->endOfDay()->utc();
        Audit::record('ledger.exported', 'report', ['from' => $data['from'], 'to' => $data['to']]);

        return response()->streamDownload(function () use ($start, $end) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Branch', 'Journal', 'Account', 'Debit paisa', 'Credit paisa'], ',', '"', '');
            foreach (LedgerEntry::with('branch', 'journal')->whereBetween('created_at', [$start, $end])->orderBy('id')->cursor() as $e) {
                $safe = fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v;
                fputcsv($out, array_map($safe, [$e->created_at->toIso8601String(), $e->branch->name, $e->journal->description, $e->account, $e->debit, $e->credit]), ',', '"', '');
            }fclose($out);
        }, 'ledger.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
