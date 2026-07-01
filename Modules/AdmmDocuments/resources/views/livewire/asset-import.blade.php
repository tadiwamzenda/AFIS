<div class="max-w-2xl space-y-4">

    @if($done)
    <div class="bg-white rounded-xl border border-green-200 p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Import complete</h3>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="bg-green-50 rounded-lg px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-green-700">{{ $imported }}</p>
                        <p class="text-xs text-green-600">Records imported</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-gray-500">{{ $skipped }}</p>
                        <p class="text-xs text-gray-400">Skipped</p>
                    </div>
                </div>
                @if(!empty($errors))
                <div class="bg-red-50 rounded-lg p-3 mb-3">
                    <p class="text-xs font-medium text-red-700 mb-1">Row errors:</p>
                    @foreach(array_slice($errors, 0, 10) as $err)
                        <p class="text-xs text-red-600">{{ $err }}</p>
                    @endforeach
                    @if(count($errors) > 10)
                        <p class="text-xs text-red-500 mt-1">... and {{ count($errors) - 10 }} more</p>
                    @endif
                </div>
                @endif
                <div class="flex gap-3 mt-4">
                    <a href="{{ route('admin.admm.asset-register.index') }}"
                        class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                        View Asset Register →
                    </a>
                    <button wire:click="$set('done', false)"
                        class="text-sm text-gray-500 hover:text-gray-700">
                        Import another file
                    </button>
                </div>
            </div>
        </div>
    </div>
    @else

    @if($error)
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Import from Excel</h3>
        <p class="text-xs text-gray-400 mb-5">
            Upload your Bantu Database Excel file. The system expects these exact column headers from the Manager File sheet.
        </p>

        <div class="bg-blue-50 rounded-lg px-4 py-3 mb-4">
            <p class="text-xs font-medium text-blue-700 mb-1">Expected column headers:</p>
            <p class="text-xs text-blue-600 font-mono leading-relaxed">
                Installation Date · Client · Vehicle Reg No · Vehicle Fleet No · Vehicle Make · GPS Device IMEI · GPS Device Name · GPS Device Type · Configuration · Gps Device State · Sim Card Serial No · Sim Card Phone No · Sim Card Type · SIM Card ISP · Location · Technician(Installer) · Comment · Client Name · Client Contact · Client Email
            </p>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 text-xs text-amber-700">
            <strong>Note:</strong> Records with a GPS Device IMEI are matched by IMEI and updated if they already exist. Records without an IMEI are always inserted as new rows.
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Select file <span class="text-red-500">*</span>
                    <span class="font-normal text-gray-400 text-xs">(xlsx, xls, csv)</span>
                </label>
                <input wire:model="file" type="file" accept=".xlsx,.xls,.csv"
                    class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            </div>

            <button wire:click="import" wire:loading.attr="disabled"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="import">Import records</span>
                <span wire:loading wire:target="import">Importing... please wait</span>
            </button>
        </div>
    </div>
    @endif
</div>