<div class="max-w-4xl space-y-4">

    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div class="flex flex-wrap gap-6 text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="radio" wire:model.live="type" value="devices"> Devices (IMEI)
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="radio" wire:model.live="type" value="sims"> SIM cards (ICCID)
            </label>
        </div>

        <div class="text-xs text-gray-500 space-y-1">
            @if($type === 'devices')
                <p>Required columns: <b>IMEI</b> (15 digits, must be unique), <b>Device_Type</b> (e.g. Concox, Teltonika, Istartek). Optional: <b>Model</b>.</p>
                <p>Duplicate IMEIs are rejected and listed in the error report. Imported devices go into office stock.</p>
            @else
                <p>Required columns: <b>ICCID</b> (18 to 22 digits, must be unique), <b>Phone_Number</b> (Zimbabwe mobile, e.g. 0771234567 or +263771234567).</p>
                <p>Phone numbers are standardised to +263 format and the network is worked out from the prefix. A phone number already on another SIM is rejected.</p>
            @endif
            <p>Tip: in Excel, format the IMEI / ICCID columns as <b>Text</b> before saving as CSV, otherwise long numbers turn into 8.6E+14.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <input type="file" wire:model="file" accept=".csv,.txt" class="text-sm">
            <button wire:click="import" wire:loading.attr="disabled" wire:target="import,file"
                    class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                <span wire:loading.remove wire:target="import">Import</span>
                <span wire:loading wire:target="import">Importing...</span>
            </button>
            <button type="button" wire:click="downloadTemplate" class="text-sm text-gray-600 underline">Download blank template</button>
            <span wire:loading wire:target="file" class="text-xs text-gray-500">Uploading file...</span>
        </div>
        @error('file') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    @if($result)
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            @if($result['fatal'])
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
                    <b>Import stopped:</b> {{ $result['fatal'] }}
                </div>
            @else
                <div class="flex flex-wrap gap-6 text-sm">
                    <span>Rows read: <b>{{ number_format($result['total']) }}</b></span>
                    <span class="text-green-700">Imported: <b>{{ number_format($result['imported']) }}</b></span>
                    <span class="{{ count($result['failed']) ? 'text-red-700' : 'text-gray-500' }}">
                        Problems: <b>{{ number_format(count($result['failed'])) }}</b>
                    </span>
                </div>

                @if(count($result['failed']))
                    <button wire:click="downloadErrors"
                            class="text-sm font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Download error report (CSV)
                    </button>

                    <div class="overflow-auto max-h-96 border border-gray-200 rounded-lg">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase sticky top-0">
                                <tr>
                                    <th class="text-left px-3 py-2">Row</th>
                                    <th class="text-left px-3 py-2">Field</th>
                                    <th class="text-left px-3 py-2">Value</th>
                                    <th class="text-left px-3 py-2">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach(array_slice($result['failed'], 0, 200) as $f)
                                <tr>
                                    <td class="px-3 py-1.5 font-mono">{{ $f['row'] }}</td>
                                    <td class="px-3 py-1.5">{{ $f['field'] }}</td>
                                    <td class="px-3 py-1.5 font-mono">{{ $f['value'] }}</td>
                                    <td class="px-3 py-1.5 text-red-700">{{ $f['reason'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($result['failed']) > 200)
                        <p class="text-xs text-gray-400">Showing the first 200 problems. The CSV report has all {{ count($result['failed']) }}.</p>
                    @endif
                @endif
            @endif
        </div>
    @endif
</div>
