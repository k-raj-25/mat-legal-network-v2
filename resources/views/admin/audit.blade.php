<x-layouts.admin title="Audit log">
    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-slate-500">{{ $log->created_at->format('d M Y, H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td class="font-mono text-xs">{{ $log->action }}</td>
                        <td class="max-w-md text-xs text-slate-600">{{ collect($log->meta)->map(fn ($v, $k) => $k.': '.(is_array($v) ? implode(', ', $v) : var_export($v, true)))->implode(' · ') }}</td>
                        <td class="text-xs text-slate-400">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-slate-500">No activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-layouts.admin>
