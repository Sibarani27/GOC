<div class="space-y-6">
    {{-- Kepala --}}
    <div>
        <a href="{{ route('goc.incidents') }}" wire:navigate class="text-sm text-slate-400 hover:text-white">← Semua insiden</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <span class="rounded bg-slate-800 px-2 py-1 font-mono text-sm text-amber-400">{{ $incident->code }}</span>
            <h1 class="text-xl font-bold text-white">{{ $incident->title }}</h1>
            <x-goc.severity-badge :severity="$incident->severity" />
            <x-goc.status-badge :status="$incident->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ \App\Support\IncidentOptions::CATEGORIES[$incident->category] ?? $incident->category }}
            · dilaporkan oleh {{ $incident->reporter?->name ?? 'sistem' }}
            · dibuka {{ $incident->opened_at?->format('d M Y H:i') }}
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Kolom utama --}}
        <div class="space-y-6 lg:col-span-2">
            @if ($incident->description)
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                    <div class="mb-2 text-sm font-semibold text-white">Deskripsi</div>
                    <p class="whitespace-pre-line text-sm text-slate-300">{{ $incident->description }}</p>
                </div>
            @endif

            {{-- Alert terkait --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900">
                <div class="border-b border-slate-800 px-4 py-3 text-sm font-semibold text-white">Alert terkait ({{ $incident->alerts->count() }})</div>
                <div class="divide-y divide-slate-800">
                    @forelse ($incident->alerts as $a)
                        <div class="flex items-center justify-between gap-3 px-4 py-3" wire:key="la-{{ $a->id }}">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-white">{{ $a->title }}</div>
                                <div class="text-xs text-slate-500">{{ $a->asset?->name ?? '-' }} · {{ $a->source_ip ?? '-' }} · {{ $a->detected_at->format('d M H:i') }}</div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <x-goc.severity-badge :severity="$a->severity" />
                                <x-goc.status-badge :status="$a->status" />
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500">Belum ada alert yang dikaitkan. Buat insiden dari halaman Alert untuk mengaitkannya.</div>
                    @endforelse
                </div>
            </div>

            {{-- Linimasa --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-4 text-sm font-semibold text-white">Linimasa penanganan</div>

                @can('incidents.manage')
                    <form wire:submit="addNote" class="mb-5">
                        <textarea wire:model="noteBody" rows="3" placeholder="Tulis catatan, temuan, atau tindakan yang dilakukan..."
                                  class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('noteBody') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        <div class="mt-2 text-right">
                            <button type="submit" class="rounded-md bg-amber-500 px-4 py-1.5 text-sm font-semibold text-slate-950 hover:bg-amber-400">Tambah catatan</button>
                        </div>
                    </form>
                @endcan

                <ol class="space-y-4 border-l border-slate-800 pl-4">
                    @forelse ($incident->notes as $n)
                        <li class="relative" wire:key="note-{{ $n->id }}">
                            <span class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full
                                {{ $n->type === 'status_change' ? 'bg-sky-400' : ($n->type === 'action' ? 'bg-emerald-400' : 'bg-amber-400') }}"></span>
                            <div class="text-xs text-slate-500">
                                {{ $n->user?->name ?? 'Sistem' }} · {{ $n->created_at->format('d M Y H:i') }}
                            </div>
                            <p class="whitespace-pre-line text-sm {{ $n->type === 'note' ? 'text-slate-200' : 'text-slate-400' }}">{{ $n->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada catatan.</li>
                    @endforelse
                </ol>
            </div>

            {{-- Lampiran --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900">
                <div class="border-b border-slate-800 px-4 py-3 text-sm font-semibold text-white">Lampiran bukti ({{ $incident->attachments->count() }})</div>

                @can('incidents.manage')
                    <div class="border-b border-slate-800 p-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="file" wire:model="upload" class="text-sm text-slate-400 file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-slate-200">
                            <button wire:click="uploadAttachment" wire:loading.attr="disabled" wire:target="upload,uploadAttachment"
                                    class="rounded-md bg-amber-500 px-4 py-1.5 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:opacity-50">Unggah</button>
                            <span wire:loading wire:target="upload" class="text-xs text-slate-500">Memuat berkas...</span>
                        </div>
                        @error('upload') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-slate-600">Maks. 10 MB. Format: pdf, png, jpg, txt, log, csv, json, zip, pcap, evtx.</p>
                    </div>
                @endcan

                <div class="divide-y divide-slate-800">
                    @forelse ($incident->attachments as $f)
                        <div class="flex items-center justify-between gap-3 px-4 py-3" wire:key="att-{{ $f->id }}">
                            <div class="min-w-0">
                                <a href="{{ route('goc.incidents.attachment', $f) }}" class="truncate text-sm font-medium text-amber-400 hover:text-amber-300">{{ $f->original_name }}</a>
                                <div class="text-xs text-slate-500">
                                    {{ number_format($f->size / 1024, 1) }} KB · {{ $f->uploader?->name ?? '-' }} · {{ $f->created_at->format('d M Y H:i') }}
                                </div>
                            </div>
                            <span class="shrink-0 font-mono text-[11px] text-slate-600" title="SHA-256: {{ $f->sha256 }}">{{ substr($f->sha256 ?? '', 0, 12) }}…</span>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500">Belum ada lampiran.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kolom samping --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Status</div>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Support\IncidentOptions::STATUSES as $key => $label)
                        @if ($key === 'closed')
                            @can('incidents.close')
                                <button wire:click="setStatus('{{ $key }}')" @disabled($incident->status === $key)
                                        class="rounded-md border px-3 py-1.5 text-sm {{ $incident->status === $key ? 'border-amber-500 bg-amber-500/10 text-amber-300' : 'border-slate-700 text-slate-300 hover:bg-slate-800' }}">{{ $label }}</button>
                            @endcan
                        @else
                            @can('incidents.manage')
                                <button wire:click="setStatus('{{ $key }}')" @disabled($incident->status === $key)
                                        class="rounded-md border px-3 py-1.5 text-sm {{ $incident->status === $key ? 'border-amber-500 bg-amber-500/10 text-amber-300' : 'border-slate-700 text-slate-300 hover:bg-slate-800' }}">{{ $label }}</button>
                            @endcan
                        @endif
                    @endforeach
                </div>
                @cannot('incidents.manage')
                    <p class="mt-2 text-xs text-slate-600">Anda hanya dapat melihat insiden ini.</p>
                @endcannot
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Penanggung jawab</div>
                @can('incidents.manage')
                    <select wire:change="assign($event.target.value)"
                            class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Belum ditugaskan</option>
                        @foreach ($analysts as $u)
                            <option value="{{ $u->id }}" @selected($incident->assignee_id === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="text-sm text-slate-300">{{ $incident->assignee?->name ?? 'Belum ditugaskan' }}</div>
                @endcan
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Waktu</div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Terdeteksi</dt><dd class="text-slate-300">{{ $incident->detected_at?->format('d M H:i') ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Dibuka</dt><dd class="text-slate-300">{{ $incident->opened_at?->format('d M H:i') ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Terkendali</dt><dd class="text-slate-300">{{ $incident->contained_at?->format('d M H:i') ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ditutup</dt><dd class="text-slate-300">{{ $incident->closed_at?->format('d M H:i') ?? '-' }}</dd></div>
                    @if ($incident->opened_at)
                        <div class="flex justify-between border-t border-slate-800 pt-2">
                            <dt class="text-slate-500">Durasi</dt>
                            <dd class="text-slate-300">{{ $incident->opened_at->diffForHumans($incident->closed_at ?? now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Akar masalah &amp; resolusi</div>
                @can('incidents.manage')
                    <form wire:submit="saveResolution" class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Akar masalah</label>
                            <textarea wire:model="rootCause" rows="3" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500"></textarea>
                            @error('rootCause') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Resolusi</label>
                            <textarea wire:model="resolution" rows="3" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500"></textarea>
                            @error('resolution') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Simpan</button>
                    </form>
                @else
                    <div class="space-y-3 text-sm text-slate-300">
                        <div><div class="text-xs uppercase text-slate-500">Akar masalah</div>{{ $incident->root_cause ?? '-' }}</div>
                        <div><div class="text-xs uppercase text-slate-500">Resolusi</div>{{ $incident->resolution ?? '-' }}</div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</div>
