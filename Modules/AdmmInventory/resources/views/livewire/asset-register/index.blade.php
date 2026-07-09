<div class="flex flex-col" style="height: calc(100vh - 130px);">


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
    <div class="bg-white rounded-xl border border-gray-200 overflow-auto flex-1">
        <table class="text-xs w-max min-w-full">
            <thead class="sticky top-0 z-30">
                <tr class="bg-gray-800 text-white">
                    <th class="sticky left-0 z-20 bg-gray-800 px-2 py-2 text-left font-medium whitespace-nowrap w-8">#</th>
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
                    <th class="sticky left-0 z-20 bg-gray-100 px-1 py-1"></th>
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
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $record)
                <tr class="hover:bg-blue-50/30">
                    <td class="sticky left-0 bg-white px-2 py-1 text-gray-400 font-mono text-xs border-r border-gray-100">{{ $loop->index + 1 }}</td>
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
                    <td colspan="22" class="px-4 py-8 text-center text-sm text-gray-400">
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