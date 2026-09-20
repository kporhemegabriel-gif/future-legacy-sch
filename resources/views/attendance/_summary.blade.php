{{-- Expects: $summary (array from AttendanceSummaryService::summaryFor), $history (paginator) --}}

<div class="grid">
    <div class="card"><div class="stat-number">{{ $summary['total'] }}</div><div class="stat-label">Days recorded</div></div>
    <div class="card"><div class="stat-number">{{ $summary['present'] }}</div><div class="stat-label">Present</div></div>
    <div class="card"><div class="stat-number">{{ $summary['absent'] }}</div><div class="stat-label">Absent</div></div>
    <div class="card"><div class="stat-number">{{ $summary['late'] }}</div><div class="stat-label">Late</div></div>
    <div class="card"><div class="stat-number">{{ $summary['excused'] }}</div><div class="stat-label">Excused</div></div>
    <div class="card">
        <div class="stat-number">{{ $summary['percentage'] !== null ? $summary['percentage'].'%' : '—' }}</div>
        <div class="stat-label">Attendance rate</div>
    </div>
</div>
<p class="muted">Attendance rate = present days ÷ total recorded days. Late and Excused count as recorded days but not as present. This reflects days attendance was actually taken — not calendar days, weekends, or holidays.</p>

<div class="card">
    <h2>History</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Status</th><th>Note</th><th>Term</th></tr></thead>
            <tbody>
                @forelse ($history as $record)
                    <tr>
                        <td>{{ $record->attendance_date->format('d M Y') }}</td>
                        <td><span class="badge badge-{{ $record->status === 'present' ? 'active' : ($record->status === 'absent' ? 'withdrawn' : 'inactive') }}">{{ ucfirst($record->status) }}</span></td>
                        <td>{{ $record->note ?? '—' }}</td>
                        <td>{{ $record->term->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">No attendance recorded for this period yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $history->links() }}</div>
</div>
