<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Support\ReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    private function data(Request $request): array
    {
        $v = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $v['from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $v['to'])->startOfDay();

        abort_if($from->diffInDays($to) >= ReportBuilder::MAX_DAYS, 422, 'Rentang laporan maksimal ' . ReportBuilder::MAX_DAYS . ' hari.');

        return ReportBuilder::build($from, $to);
    }

    private function log(Request $request, string $format, array $r): void
    {
        activity('report')->causedBy($request->user())
            ->withProperties(['format' => $format, 'from' => $r['from'], 'to' => $r['to']])
            ->log('Laporan diekspor (' . strtoupper($format) . ')');
    }

    public function pdf(Request $request)
    {
        $r = $this->data($request);
        $this->log($request, 'pdf', $r);

        return Pdf::loadView('reports.pdf', ['r' => $r, 'by' => $request->user()->name])
            ->setPaper('a4')
            ->download("laporan-goc-{$r['from']}_{$r['to']}.pdf");
    }

    public function excel(Request $request)
    {
        $r = $this->data($request);
        $this->log($request, 'xlsx', $r);

        return Excel::download(new ReportExport($r), "laporan-goc-{$r['from']}_{$r['to']}.xlsx");
    }
}
