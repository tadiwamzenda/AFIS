<div class="space-y-6">

    {{-- ── Debug Info (Temporary - remove after fixing) ────────────── --}}
    <div class="mb-4 p-3 bg-gray-100 border border-gray-300 rounded-lg text-xs">
        <p><strong>Debug:</strong> {{ $debugInfo }}</p>
        @if($error)
            <p class="text-red-600"><strong>Error:</strong> {{ $error }}</p>
        @endif
        <p>Previewing: {{ $previewing ? 'Yes' : 'No' }} | Imported: {{ $imported ? 'Yes' : 'No' }} | Rows: {{ count($previewRows) }}</p>
    </div>

    {{-- ── Template download notice ────────────────────────────────── --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-4">
        <p class="text-sm font-medium text-blue-800 mb-1">Required file format</p>
        <p class="text-xs text-blue-600">
            Your Excel file must have exactly these 4 column headers in row 1:
            <span class="font-mono font-semibold">SIM Card Number</span> ·
            <span class="font-mono font-semibold">ISP Provider</span> ·
            <span class="font-mono font-semibold">Batch Code</span> ·
            <span class="font-mono font-semibold">Type</span>
        </p>
    </div>

    {{-- ── Upload form ─────────────────────────────────────────────── --}}
    @if(!$previewing && !$imported)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Upload SIM card schedule</h3>

        @if($error)
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $error }}
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Excel file (.xlsx) <span class="text-red-500">*</span>
                </label>
                <input wire:model="file" type="file" accept=".xlsx,.xls"
                    class="block text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-500 file:text-white hover:file:bg-brand-600 cursor-pointer">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button wire:click="previewImport"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                <span wire:loading.remove wire:target="preview">Preview import</span>
                <span wire:loading wire:target="preview">Reading file...</span>
            </button>
        </div>
    </div>
    @endif

    {{-- ── Preview ─────────────────────────────────────────────────── --}}
    @if($previewing && !$imported)
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Preview — first 10 rows</h3>
                <p class="text-xs text-gray-400 mt-0.5">Confirm the data looks correct before importing</p>
            </div>
            <button wire:click="$set('previewing', false)"
                class="text-xs text-gray-400 hover:text-gray-600">← Back</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-2 font-medium text-gray-600">SIM Card Number</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">ISP Provider</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Batch Code</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($previewRows as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-gray-700">{{ is_array($row) ? ($row['msisdn'] ?? $row[0] ?? '—') : '—' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ is_array($row) ? ($row['network_provider'] ?? $row[1] ?? '—') : '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ is_array($row) ? ($row['batch_code'] ?? $row[2] ?? '—') : '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ is_array($row) ? ($row['bundle_type'] ?? $row[3] ?? '—') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
            <button wire:click="import" wire:loading.attr="disabled"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="import">Confirm &amp; import all rows</span>
                <span wire:loading wire:target="import">Importing...</span>
            </button>
            <button wire:click="$set('previewing', false)"
                class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    </div>
    @endif

    {{-- ── Result ───────────────────────────────────────────────────── --}}
    @if($imported && $result)
    <div class="bg-white rounded-xl border border-green-200 p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Import complete</h3>
                <div class="flex gap-6">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $result['created_count'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">New SIM cards</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $result['updated_count'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">Updated</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $result['skipped_count'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">Skipped</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $result['total_rows'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">Total rows</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-3">
                    <button wire:click="resetForm"
                        class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                        Import another file
                    </button>
                    <a href="{{ route('admin.admm.sim-cards.index') }}"
                        class="text-sm text-gray-500 hover:text-gray-700">
                        View SIM cards →
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Import history ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Import history</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($history as $import)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $import->filename }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $import->importedBy?->name ?? 'Unknown' }} ·
                            {{ \Carbon\Carbon::parse($import->created_at)->format('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs">
                            <span class="text-green-600 font-medium">+{{ $import->created_count }} new</span>
                            · <span class="text-blue-600 font-medium">↑{{ $import->updated_count }} updated</span>
                            · <span class="text-gray-400">{{ $import->total_rows }} total</span>
                        </p>
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">No imports yet.</p>
            @endforelse
        </div>
    </div>

</div>