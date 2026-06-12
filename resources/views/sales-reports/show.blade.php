<x-app-layout title="{{ $reportTitle }} - Emicon Station Manager">
    <x-dashboard-layout header="Sales Reports">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Sales report</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $reportTitle }}</h2>
                <p class="mt-2 text-sm text-slate-600">Apply filters then export the filtered dataset to Excel or PDF.</p>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" placeholder="YYYY-MM-DD" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" placeholder="YYYY-MM-DD" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</label>
                        <select name="branch_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" @disabled(auth()->user()->branch_id !== null)>
                            <option value="all">All branches</option>
                            @foreach($filterOptions['branches'] as $branch)
                                <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? 'all') == (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Product</label>
                        <select name="product" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">All products</option>
                            @foreach($filterOptions['products'] as $product)
                                <option value="{{ $product }}" @selected(($filters['product'] ?? '') === $product)>{{ $product }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Attendant</label>
                        <select name="attendant_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">All attendants</option>
                            @foreach($filterOptions['attendants'] as $attendant)
                                <option value="{{ $attendant->id }}" @selected(($filters['attendant_id'] ?? '') == (string) $attendant->id)>{{ $attendant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 lg:col-span-5 flex flex-wrap items-center gap-2">
                        <button class="rounded-lg bg-[#0e2382] px-4 py-2 text-sm font-semibold text-white">Apply filters</button>
                        <a href="{{ route('sales-reports.'.$reportKey) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Reset</a>
                        <a href="{{ route('sales-reports.export', ['report' => $reportKey, ...request()->query(), 'format' => 'xlsx']) }}" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">Export Excel</a>
                        <a href="{{ route('sales-reports.export', ['report' => $reportKey, ...request()->query(), 'format' => 'pdf']) }}" class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">Export PDF</a>
                    </div>
                </form>
            </section>

            <div class="grid gap-5 xl:grid-cols-12">
                <section class="xl:col-span-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Quick stats</h3>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Transactions</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($summary['transactions'], 0) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Volume</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($summary['volume'], 1) }} L</p>
                        </div>
                    </div>
                </section>

                <section class="xl:col-span-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Filtered records</h3>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-600">Top 75 shown</span>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Date</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Branch</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Attendant</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Product</th>
                                    <th class="px-3 py-2 text-right font-medium text-slate-600">Liters</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-slate-700">{{ $row['date'] }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $row['branch'] }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $row['attendant'] }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $row['product'] }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ number_format($row['liters'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">No records found for current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>
