<?php

namespace Modules\AdmmReports\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdmmReports\Exports\GenericCollectionExport;
use Modules\AdmmReports\Services\ReportDataService;
use Illuminate\Support\Collection;

use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\Accessory;


class ReportController extends Controller
{
    public function __construct(protected ReportDataService $data) {}

    // ─── Hub ─────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('admmreports::reports.index');
    }

    // ─── SIM card reports ─────────────────────────────────────────────────────

    public function simInventory(Request $request)
    {
        $rows = $this->data->simCardInventory();
        if ($request->format === 'excel') return $this->excelSimCards($rows, 'sim-card-inventory');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.sim-inventory', compact('rows'), 'sim-card-inventory');
        return view('admmreports::reports.sim-inventory', compact('rows'));
    }

    public function simByProvider(Request $request)
    {
        $groups = $this->data->simCardsByProvider();
        if ($request->format === 'excel') return $this->excelSimCards($groups->flatten(), 'sim-by-provider');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.sim-by-provider', compact('groups'), 'sim-by-provider');
        return view('admmreports::reports.sim-by-provider', compact('groups'));
    }

    public function simByBatch(Request $request)
    {
        $groups = $this->data->simCardsByBatch();
        if ($request->format === 'excel') return $this->excelSimCards($groups->flatten(), 'sim-by-batch');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.sim-by-batch', compact('groups'), 'sim-by-batch');
        return view('admmreports::reports.sim-by-batch', compact('groups'));
    }

    public function simByStatus(Request $request)
    {
        $groups = $this->data->simCardsByStatus();
        if ($request->format === 'excel') return $this->excelSimCards($groups->flatten(), 'sim-by-status');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.sim-by-status', compact('groups'), 'sim-by-status');
        return view('admmreports::reports.sim-by-status', compact('groups'));
    }

    // ─── GPS device reports ───────────────────────────────────────────────────

    public function deviceInventory(Request $request)
    {
        $rows = $this->data->gpsDeviceInventory();
        if ($request->format === 'excel') return $this->excelDevices($rows, 'device-inventory');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.device-inventory', compact('rows'), 'device-inventory');
        return view('admmreports::reports.device-inventory', compact('rows'));
    }

    public function devicesByClient(Request $request)
    {
        $groups = $this->data->gpsDevicesByClient();
        if ($request->format === 'excel') return $this->excelDevices($groups->flatten(), 'devices-by-client');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.devices-by-client', compact('groups'), 'devices-by-client');
        return view('admmreports::reports.devices-by-client', compact('groups'));
    }

    public function devicesInStock(Request $request)
    {
        $rows = $this->data->devicesInStock();
        if ($request->format === 'excel') return $this->excelDevices($rows, 'devices-in-stock');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.devices-in-stock', compact('rows'), 'devices-in-stock');
        return view('admmreports::reports.devices-in-stock', compact('rows'));
    }

    // ─── Accessory reports ────────────────────────────────────────────────────

    public function accessoryInventory(Request $request)
    {
        $rows = $this->data->accessoryInventory();
        if ($request->format === 'excel') return $this->excelAccessories($rows, 'accessory-inventory');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.accessory-inventory', compact('rows'), 'accessory-inventory');
        return view('admmreports::reports.accessory-inventory', compact('rows'));
    }

    public function accessoriesByType(Request $request)
    {
        $groups = $this->data->accessoriesByType();
        if ($request->format === 'excel') return $this->excelAccessories($groups->flatten(), 'accessories-by-type');
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.accessories-by-type', compact('groups'), 'accessories-by-type');
        return view('admmreports::reports.accessories-by-type', compact('groups'));
    }

    // ─── Combined reports ─────────────────────────────────────────────────────

    public function assignmentChain(Request $request)
    {
        $clients = $this->data->assignmentChain();
        if ($request->format === 'pdf') return $this->pdfView('admmreports::pdf.assignment-chain', compact('clients'), 'assignment-chain');
        return view('admmreports::reports.assignment-chain', compact('clients'));
    }


     // ─── Combined reports ─────────────────────────────────────────────────────


    public function internalStock(Request $request)
    {
        // Get SIM cards in stock (location_context = 'internal_stock')
        $sims = SimCard::where('location_context', 'internal_stock')->get();
        
        // Get devices in stock (location_context = 'internal_stock')
        $devices = GpsDevice::where('location_context', 'internal_stock')->get();
        
        // Get accessories in stock (location_context = 'internal_stock')
        $accessories = Accessory::where('location_context', 'internal_stock')->get();
        
        // Check if export is requested
        if ($request->get('format') === 'excel') {
            return back()->with('error', 'Excel export not implemented yet');
        }
        
        if ($request->get('format') === 'pdf') {
            return view('admmreports::pdf.internal-stock', compact('sims', 'devices', 'accessories'));
        }
        
        return view('admmreports::reports.internal-stock', compact('sims', 'devices', 'accessories'));
    }

    public function auditTrail(Request $request)
    {
        $filters = $request->only(['module', 'event', 'date_from', 'date_to']);
        $rows    = $this->data->auditTrail($filters);
        if ($request->format === 'excel') return $this->excelAuditTrail($rows);
        if ($request->format === 'pdf')   return $this->pdfView('admmreports::pdf.audit-trail', compact('rows'), 'audit-trail');
        return view('admmreports::reports.audit-trail', compact('rows', 'filters'));
    }

    // ─── Excel helpers ────────────────────────────────────────────────────────

    private function excelSimCards(Collection $rows, string $filename)
    {
        $export = new GenericCollectionExport(
            $rows->map(fn($s) => [
                $s->iccid,
                $s->msisdn,
                $s->network_provider,
                $s->batch_code,
                $s->bundle_type,
                $s->status_label,
                ucfirst(str_replace('_', ' ', $s->location_context)),
                $s->client?->name ?? '—',
            ]),
            ['ICCID', 'MSISDN', 'Provider', 'Batch Code', 'Type', 'Status', 'Location', 'Client']
        );
        return Excel::download($export, $filename . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function excelDevices(Collection $rows, string $filename)
    {
        $export = new GenericCollectionExport(
            $rows->map(fn($d) => [
                $d->serial_number,
                $d->model,
                $d->firmware_version,
                $d->status_label,
                ucfirst(str_replace('_', ' ', $d->location_context)),
                $d->client?->name ?? '—',
                $d->vehicle_registration ?? '—',
                $d->simCard?->msisdn ?? '—',
            ]),
            ['Serial Number', 'Model', 'Firmware', 'Status', 'Location', 'Client', 'Vehicle', 'SIM Card']
        );
        return Excel::download($export, $filename . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function excelAccessories(Collection $rows, string $filename)
    {
        $export = new GenericCollectionExport(
            $rows->map(fn($a) => [
                $a->accessoryType?->name ?? '—',
                $a->serial_number ?? '—',
                $a->status_label,
                ucfirst(str_replace('_', ' ', $a->location_context)),
                $a->client?->name ?? '—',
                $a->gpsDevice?->serial_number ?? '—',
            ]),
            ['Type', 'Serial Number', 'Status', 'Location', 'Client', 'Device']
        );
        return Excel::download($export, $filename . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function excelAuditTrail(Collection $rows)
    {
        $export = new GenericCollectionExport(
            $rows->map(fn($l) => [
                $l->created_at->format('d M Y H:i'),
                $l->user?->name ?? 'System',
                $l->module,
                $l->event,
                $l->entity_type ?? '—',
                $l->entity_id ?? '—',
                json_encode($l->data),
            ]),
            ['Date', 'User', 'Module', 'Event', 'Entity Type', 'Entity ID', 'Data']
        );
        return Excel::download($export, 'audit-trail-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function pdfView(string $view, array $data, string $filename)
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');
        return $pdf->download($filename . '-' . now()->format('Y-m-d') . '.pdf');
    }
}