@extends('layouts.app')

@section('content')
	<div class="page-head">
		<div>
			<div class="eyebrow">REPORT USER WORKSPACE</div>
			<h1>Public reports dashboard</h1>
			<p class="muted">View transport records and monitor report processing.</p>
		</div>
	</div>

	<div class="stat-grid">
		<div class="stat-card">
			<span class="stat-icon blue"><i class="bi bi-file-earmark-text"></i></span>
			<div><small>Total reports</small><strong>{{ $stats['total'] }}</strong></div>
		</div>
		<div class="stat-card">
			<span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span>
			<div><small>Pending</small><strong>{{ $stats['pending'] }}</strong></div>
		</div>
		<div class="stat-card">
			<span class="stat-icon blue"><i class="bi bi-search"></i></span>
			<div><small>Under review</small><strong>{{ $stats['review'] }}</strong></div>
		</div>
		<div class="stat-card">
			<span class="stat-icon teal"><i class="bi bi-check2-circle"></i></span>
			<div><small>Resolved / closed</small><strong>{{ $stats['resolved'] }}</strong></div>
		</div>
	</div>

	<div class="panel mt-4">
		<div class="panel-head">
			<div>
				<h3>Recent reports</h3>
				<p class="muted">View-only public report records</p>
			</div>
			<a href="{{ route('reports.index') }}" class="text-link">View all <i class="bi bi-arrow-up-right"></i></a>
		</div>
		<div class="table-responsive">
			<table class="table align-middle">
				<thead>
					<tr>
						<th scope="col">Report ID</th>
						<th scope="col">Date submitted</th>
						<th scope="col">Type</th>
						<th scope="col">Location</th>
						<th scope="col">Status</th>
						<th scope="col" aria-label="Actions"></th>
					</tr>
				</thead>
				<tbody>
					@forelse($reports as $report)
						@php
							$statusTheme = match (strtolower($report->status)) {
								'pending' => 'pending',
								'under review' => 'review',
								'resolved' => 'resolved',
								'closed' => 'closed',
								default => 'other',
							};
						@endphp
						<tr>
							<td>{{ $report->report_number }}</td>
							<td>{{ $report->date_submitted->format('d M Y') }}</td>
							<td>{{ $report->report_type }}</td>
							<td>{{ $report->location ?: '—' }}</td>
							<td><span class="report-status {{ $statusTheme }}">{{ $report->status }}</span></td>
							<td><a class="btn btn-sm btn-light border" href="{{ route('reports.show', $report) }}"><i class="bi bi-eye"></i> View</a></td>
						</tr>
					@empty
						<tr>
							<td colspan="6" class="report-empty-cell">
								<div class="report-empty">
									<i class="bi bi-file-earmark-text" aria-hidden="true"></i>
									<strong>No reports yet</strong>
									<p>Public reports submitted by users will appear here.</p>
									@can('view my reports')
										<a class="btn btn-primary btn-sm" href="{{ route('reports.create') }}">Create your first report <i class="bi bi-arrow-right ms-1"></i></a>
									@endcan
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		{{ $reports->links() }}
	</div>
@endsection
