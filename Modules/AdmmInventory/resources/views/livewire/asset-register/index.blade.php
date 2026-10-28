<div class="flex flex-col asset-grid" style="height: calc(100vh - 130px);" x-data="assetGrid()">

<style>
    .asset-grid [x-cloak] { display: none !important; }
    /* rows ticked for deletion */
    .asset-grid tbody tr:has(.row-check:checked) > td { background-color: #fef3c7; }
    /* current row: whole row tinted while any of its cells has focus */
    .asset-grid tbody tr:focus-within > td { background-color: #e6f2ef; }
    /* current cell */
    .asset-grid tbody tr:focus-within > td:focus-within { background-color: #cfe6df; outline: 2px solid #085041; outline-offset: -2px; }
    /* keep the focused cell clear of the sticky headers / sticky left columns */
    .asset-grid tbody [wire\:change] { scroll-margin-top: 4.5rem; scroll-margin-left: 3.5rem; }
</style>

@verbatim
<script>
window.assetGrid = window.assetGrid || function () {
    return {
        count: 0,
        allChecked: false,
        editing: false,
        original: '',

        init() {
            this.$nextTick(() => {
                this.refresh();
                // rows added/removed (filters, delete, add row) -> recount selection
                new MutationObserver(() => this.refresh())
                    .observe(this.$refs.body, { childList: true });
            });
        },

        // ── helpers ──────────────────────────────────────────────────────
        rows() { return Array.from(this.$refs.body.querySelectorAll('tr[data-id]')); },
        cells(row) { return Array.from(row.querySelectorAll('[wire\\:change]')); },
        isCell(el) {
            return !!(el && el.matches && el.matches('[wire\\:change]') && this.$refs.body.contains(el));
        },
        checkedIds() {
            return Array.from(this.$refs.body.querySelectorAll('.row-check:checked'))
                .map(c => parseInt(c.value, 10));
        },
        locate(el) {
            const row = el.closest('tr[data-id]');
            if (!row) return null;
            return { rowIndex: this.rows().indexOf(row), colIndex: this.cells(row).indexOf(el) };
        },

        // ── selection / delete ───────────────────────────────────────────
        refresh() {
            const boxes = this.$refs.body.querySelectorAll('.row-check');
            this.count = this.checkedIds().length;
            this.allChecked = boxes.length > 0 && this.count === boxes.length;
        },
        toggleAll(on) {
            this.$refs.body.querySelectorAll('.row-check').forEach(c => { c.checked = on; });
            this.refresh();
        },
        deleteSelected() {
            const ids = this.checkedIds();
            if (!ids.length) return;
            if (!confirm('Delete ' + ids.length + ' selected record(s)? This cannot be undone.')) return;
            this.$wire.deleteSelected(ids).then(() => this.refresh());
        },

        // ── navigation ───────────────────────────────────────────────────
        moveTo(r, c) {
            const rows = this.rows();
            if (!rows.length) return;
            r = Math.max(0, Math.min(rows.length - 1, r));
            const cells = this.cells(rows[r]);
            if (!cells.length) return;
            c = Math.max(0, Math.min(cells.length - 1, c));
            cells[c].focus(); // onFocusIn resets to navigation mode and selects the text
            cells[c].scrollIntoView({ block: 'nearest', inline: 'nearest' });
        },
        startEdit(el) {
            this.editing = true;
            if (el.tagName === 'INPUT' && el.type !== 'date') {
                try { const n = el.value.length; el.setSelectionRange(n, n); } catch (err) {}
            }
        },
        onFocusIn(e) {
            if (!this.isCell(e.target)) return;
            this.editing = false;
            this.original = e.target.value;
            if (e.target.tagName === 'INPUT' && e.target.type !== 'date') {
                try { e.target.select(); } catch (err) {}
            }
        },
        onClick(e) {
            // clicking into a cell = you want to type, so the caret keys work normally
            if (this.isCell(e.target)) this.editing = true;
        },
        onKey(e) {
            const el = e.target;
            if (!this.isCell(el)) return;
            if (e.ctrlKey || e.metaKey) return; // Ctrl+C / Ctrl+V / Ctrl+A go to their own handlers
            const pos = this.locate(el);
            if (!pos) return;

            const key = e.key;
            const isSelect = el.tagName === 'SELECT';
            const isDate = el.type === 'date';

            if (key === 'F2') { e.preventDefault(); this.startEdit(el); return; }

            if (key === 'Escape') {
                if (this.editing && el.tagName === 'INPUT') el.value = this.original;
                this.editing = false;
                if (el.tagName === 'INPUT' && !isDate) { try { el.select(); } catch (err) {} }
                e.preventDefault();
                return;
            }

            if (key === 'Enter') {
                e.preventDefault();
                // moving focus away commits the cell (fires its wire:change)
                this.moveTo(pos.rowIndex + (e.shiftKey ? -1 : 1), pos.colIndex);
                return;
            }

            if (key === 'ArrowUp' || key === 'ArrowDown' || key === 'ArrowLeft' || key === 'ArrowRight') {
                const vertical = key === 'ArrowUp' || key === 'ArrowDown';
                // while editing a dropdown/date, the arrows belong to that control
                if (this.editing && (isSelect || isDate)) return;
                // while editing text, left/right move the caret
                if (this.editing && !vertical) return;
                e.preventDefault();
                this.moveTo(
                    pos.rowIndex + (key === 'ArrowDown' ? 1 : key === 'ArrowUp' ? -1 : 0),
                    pos.colIndex + (key === 'ArrowRight' ? 1 : key === 'ArrowLeft' ? -1 : 0)
                );
                return;
            }

            // typing a character starts editing (the text was selected, so it replaces it)
            if (!this.editing && key.length === 1 && !e.altKey) this.editing = true;
        },

        // ── copy / paste ─────────────────────────────────────────────────
        onCopy(e) {
            const el = e.target;
            if (!this.isCell(el)) return;
            if (this.editing && el.tagName === 'INPUT') return; // normal text copy while editing
            e.clipboardData.setData('text/plain', el.value);
            e.preventDefault();
        },
        onPaste(e) {
            const el = e.target;
            if (!this.isCell(el)) return;
            const text = (e.clipboardData || window.clipboardData).getData('text');
            // single-line paste into text you're editing: let the browser do it
            if (this.editing && el.tagName === 'INPUT' && el.type !== 'date' && !/[\t\r\n]/.test(text)) return;
            e.preventDefault();
            const pos = this.locate(el);
            if (!pos) return;

            const grid = text.replace(/\r\n?/g, '\n').replace(/\n$/, '').split('\n')
                .map(line => line.split('\t'));
            const rows = this.rows();
            grid.forEach((line, dr) => {
                const row = rows[pos.rowIndex + dr];
                if (!row) return;
                const cells = this.cells(row);
                line.forEach((val, dc) => {
                    const cell = cells[pos.colIndex + dc];
                    if (cell) this.setCell(cell, val);
                });
            });
        },
        setCell(cell, raw) {
            let val = raw.trim();
            if (cell.tagName === 'SELECT') {
                const want = val.toLowerCase();
                const opt = Array.from(cell.options).find(o =>
                    o.value.toLowerCase() === want || o.text.trim().toLowerCase() === want);
                if (!opt) return; // not a valid option for this dropdown: skip
                val = opt.value;
            } else if (cell.type === 'date') {
                const m = val.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/);
                if (m) val = m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
                else if (val !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(val)) return;
            }
            if (cell.value === val) return;
            cell.value = val;
            // fires the cell's own wire:change -> updateCell() -> staged until "Save changes"
            cell.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
};
</script>
@endverbatim


    {{-- Stats + Actions --}}
    <div class="flex items-center gap-4 flex-wrap flex-shrink-0 mb-3">
        <div class="flex items-center gap-3 text-sm text-gray-500">
            <span class="font-medium text-gray-800">{{ number_format($stats['total']) }} total records</span>
            <span class="text-blue-600 font-medium">📱 {{ number_format($stats['devices_client']) }} devices with client</span>
            <span class="text-blue-400 font-medium">{{ number_format($stats['devices_stock']) }} devices in stock</span>
            <span class="text-green-600 font-medium">📶 {{ number_format($stats['sims_client']) }} SIMs with client</span>
            <span class="text-green-400 font-medium">{{ number_format($stats['sims_stock']) }} SIMs in stock</span>
            @if($stats['lost'] > 0)
            <span class="text-red-600 font-medium">{{ number_format($stats['lost']) }} lost</span>
            @endif
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('admin.admm.asset-register.import') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Excel
            </a>
            <button wire:click="exportExcel"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </button>
            <button wire:click="exportPdf"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </button>
            <button type="button" x-show="count > 0" x-cloak @click="deleteSelected()"
                class="inline-flex items-center gap-2 text-sm font-medium text-red-600 hover:text-red-700 px-3 py-2 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Delete selected (<span x-text="count"></span>)
            </button>
            <button wire:click="addRow"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add row
            </button>
            <button wire:click="saveAll" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="saveAll">Save changes</span>
                <span wire:loading wire:target="saveAll">Saving...</span>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-auto flex-1"
        @keydown="onKey($event)" @focusin="onFocusIn($event)" @click="onClick($event)"
        @copy="onCopy($event)" @paste="onPaste($event)">
        <table class="text-xs w-max min-w-full">
            <thead class="sticky top-0 z-30">
                <tr class="bg-gray-800 text-white">
                    <th class="sticky left-0 z-20 bg-gray-800 px-2 py-2" style="width:2rem;min-width:2rem">
                        <input type="checkbox" title="Select all" :checked="allChecked" @change="toggleAll($event.target.checked)"
                            class="w-3.5 h-3.5 rounded border-gray-300 text-brand-500">
                    </th>
                    <th class="sticky left-0 z-20 bg-gray-800 px-2 py-2 text-left font-medium whitespace-nowrap w-8" style="left:2rem">#</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Installation Date</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Client</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Vehicle Reg No</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Vehicle Fleet No</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Vehicle Make</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">GPS Device IMEI</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">GPS Device Name</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">GPS Device Type</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Configuration</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">GPS Device State</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Sim Card Serial No</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Sim Card Phone No</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Sim Card Type</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">SIM Card ISP</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Location</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Technician(Installer)</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Comment</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Client Name</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Client Contact</th>
                    <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Client Email</th>
                    <th class="px-2 py-2 w-8"></th>
                </tr>
                <tr class="bg-gray-100 border-b border-gray-200">
                    <th class="sticky left-0 z-20 bg-gray-100 px-1 py-1" style="width:2rem;min-width:2rem"></th>
                    <th class="sticky left-0 z-20 bg-gray-100 px-1 py-1" style="left:2rem"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fInstallDate" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fClient" type="text" placeholder="Filter..." class="w-28 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fVehicleReg" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fFleetNo" type="text" placeholder="Filter..." class="w-20 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fVehicleMake" type="text" placeholder="Filter..." class="w-28 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fImei" type="text" placeholder="Filter..." class="w-32 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fDeviceName" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fDeviceType" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fConfig" type="text" placeholder="Filter..." class="w-28 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1">
                        <select wire:model.live="fDeviceState" class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">All</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::DEVICE_STATES as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fSimSerial" type="text" placeholder="Filter..." class="w-32 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fSimPhone" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1">
                        <select wire:model.live="fSimType" class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">All</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::SIM_TYPES as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="px-1 py-1">
                        <select wire:model.live="fSimIsp" class="w-20 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">All</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::SIM_ISPS as $isp)
                                <option value="{{ $isp }}">{{ $isp }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="px-1 py-1">
                        <select wire:model.live="fLocation" class="w-20 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">All</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::LOCATIONS as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="px-1 py-1"><input wire:model.live.debounce.500ms="fTechnician" type="text" placeholder="Filter..." class="w-24 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"></th>
                    <th class="px-1 py-1"></th>
                    <th class="px-1 py-1"></th>
                    <th class="px-1 py-1"></th>
                    <th class="px-1 py-1"></th>
                    <th class="px-1 py-1"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" x-ref="body">
                @foreach($records as $record)
                <tr wire:key="asset-row-{{ $record->id }}" data-id="{{ $record->id }}" class="hover:bg-blue-50/30">
                    <td class="sticky left-0 bg-white px-2 py-1 border-r border-gray-100" style="width:2rem;min-width:2rem">
                        <input type="checkbox" value="{{ $record->id }}" @change="refresh()"
                            class="row-check w-3.5 h-3.5 rounded border-gray-300 text-brand-500">
                    </td>
                    <td class="sticky left-0 bg-white px-2 py-1 text-gray-400 font-mono text-xs border-r border-gray-100" style="left:2rem">{{ $loop->index + 1 }}</td>
                    <td class="px-1 py-0.5">
                        <input type="date" value="{{ $record->installation_date?->format('Y-m-d') }}"
                            wire:change="updateCell({{ $record->id }}, 'installation_date', $event.target.value)"
                            class="w-28 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->client }}"
                            wire:change="updateCell({{ $record->id }}, 'client', $event.target.value)"
                            class="w-32 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->vehicle_reg_no }}"
                            wire:change="updateCell({{ $record->id }}, 'vehicle_reg_no', $event.target.value)"
                            class="w-24 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs uppercase focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->vehicle_fleet_no }}"
                            wire:change="updateCell({{ $record->id }}, 'vehicle_fleet_no', $event.target.value)"
                            class="w-20 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->vehicle_make }}"
                            wire:change="updateCell({{ $record->id }}, 'vehicle_make', $event.target.value)"
                            class="w-32 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->gps_device_imei }}"
                            wire:change="updateCell({{ $record->id }}, 'gps_device_imei', $event.target.value)"
                            class="w-36 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs font-mono focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->gps_device_name }}"
                            wire:change="updateCell({{ $record->id }}, 'gps_device_name', $event.target.value)"
                            class="w-24 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->gps_device_type }}"
                            wire:change="updateCell({{ $record->id }}, 'gps_device_type', $event.target.value)"
                            list="device-types-list"
                            class="w-24 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->configuration }}"
                            wire:change="updateCell({{ $record->id }}, 'configuration', $event.target.value)"
                            class="w-32 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <select wire:change="updateCell({{ $record->id }}, 'gps_device_state', $event.target.value)"
                            class="w-28 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white
                            {{ $record->gps_device_state === 'ACTIVE' ? 'text-green-700' : ($record->gps_device_state === 'MALFUNCTION' ? 'text-red-700' : 'text-gray-500') }}">
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::DEVICE_STATES as $state)
                                <option value="{{ $state }}" {{ $record->gps_device_state === $state ? 'selected' : '' }}>{{ $state }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->sim_card_serial_no }}"
                            wire:change="updateCell({{ $record->id }}, 'sim_card_serial_no', $event.target.value)"
                            class="w-36 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs font-mono focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->sim_card_phone_no }}"
                            wire:change="updateCell({{ $record->id }}, 'sim_card_phone_no', $event.target.value)"
                            class="w-24 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <select wire:change="updateCell({{ $record->id }}, 'sim_card_type', $event.target.value)"
                            class="w-28 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                            <option value="">—</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::SIM_TYPES as $type)
                                <option value="{{ $type }}" {{ $record->sim_card_type === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-1 py-0.5">
                        <select wire:change="updateCell({{ $record->id }}, 'sim_card_isp', $event.target.value)"
                            class="w-20 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                            <option value="">—</option>
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::SIM_ISPS as $isp)
                                <option value="{{ $isp }}" {{ $record->sim_card_isp === $isp ? 'selected' : '' }}>{{ $isp }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-1 py-0.5">
                        <select wire:change="updateCell({{ $record->id }}, 'location', $event.target.value)"
                            class="w-20 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs font-medium focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white
                            {{ $record->location === 'CLIENT' ? 'text-green-700' : ($record->location === 'LOST' ? 'text-red-700' : 'text-blue-700') }}">
                            @foreach(\Modules\AdmmInventory\Models\AssetRecord::LOCATIONS as $loc)
                                <option value="{{ $loc }}" {{ $record->location === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->technician }}"
                            wire:change="updateCell({{ $record->id }}, 'technician', $event.target.value)"
                            class="w-28 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->comment }}"
                            wire:change="updateCell({{ $record->id }}, 'comment', $event.target.value)"
                            class="w-36 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->client_name }}"
                            wire:change="updateCell({{ $record->id }}, 'client_name', $event.target.value)"
                            class="w-28 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="text" value="{{ $record->client_contact }}"
                            wire:change="updateCell({{ $record->id }}, 'client_contact', $event.target.value)"
                            class="w-24 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-1 py-0.5">
                        <input type="email" value="{{ $record->client_email }}"
                            wire:change="updateCell({{ $record->id }}, 'client_email', $event.target.value)"
                            class="w-36 px-1 py-0.5 border-0 bg-transparent hover:bg-white hover:border hover:border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white">
                    </td>
                    <td class="px-2 py-0.5">
                        <button wire:click="deleteRow({{ $record->id }})"
                            wire:confirm="Delete this record? This cannot be undone."
                            class="text-gray-300 hover:text-red-500 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                @endforeach
                @if($records->isEmpty())
                <tr>
                    <td colspan="23" class="px-4 py-8 text-center text-sm text-gray-400">
                        No records found. Adjust filters or import from Excel.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        <datalist id="device-types-list">
            @foreach(\Modules\AdmmInventory\Models\AssetRecord::DEVICE_TYPES as $type)
                <option value="{{ $type }}">
            @endforeach
        </datalist>
    </div>

</div>