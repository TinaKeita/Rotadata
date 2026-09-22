<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $group->name }} — {{ $season['label'] }} season report</title>
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
	<style>
		* { box-sizing: border-box; }

		body {
			margin: 0;
			font-family: 'Figtree', system-ui, -apple-system, sans-serif;
			color: #1f2937;
			background: #f3f4f6;
		}

		.toolbar {
			position: sticky;
			top: 0;
			z-index: 10;
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: .5rem;
			padding: .75rem 1rem;
			background: #fff;
			border-bottom: 1px solid #e5e7eb;
		}

		.toolbar a, .toolbar button {
			font: inherit;
			font-size: .82rem;
			font-weight: 600;
			text-decoration: none;
			padding: .4rem .8rem;
			border-radius: .5rem;
			border: 1px solid #d1d5db;
			background: #fff;
			color: #374151;
			cursor: pointer;
		}

		.toolbar .primary { background: #4f6150; border-color: #4f6150; color: #fff; }
		.toolbar .spacer { flex: 1 1 auto; }

		.sheet { max-width: 900px; margin: 0 auto; padding: 12mm 8mm; }

		h1 { font-size: 1.4rem; margin: 0 0 .15rem; }
		.subtitle { color: #6b7280; font-size: .9rem; margin: 0 0 1.5rem; }

		h2 {
			font-size: .78rem;
			text-transform: uppercase;
			letter-spacing: .08em;
			color: #4f6150;
			margin: 2rem 0 .6rem;
			border-bottom: 1px solid #e5e7eb;
			padding-bottom: .35rem;
		}

		table { width: 100%; border-collapse: collapse; font-size: .85rem; }
		th, td { text-align: left; padding: .45rem .5rem; border-bottom: 1px solid #eef0ee; }
		th { color: #6b7280; font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
		td.num, th.num { text-align: right; }

		.days-high { color: #b45309; font-weight: 600; }
		.empty { padding: 1.5rem 0; text-align: center; color: #9ca3af; font-size: .85rem; }

		@media print {
			body { background: #fff; }
			.toolbar { display: none; }
			.sheet { padding: 0; max-width: none; }
			@page { size: A4; margin: 14mm; }
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
		<a href="{{ route('admin.dashboard') }}">&larr; Back to dashboard</a>
		<span class="spacer"></span>
	</div>

	<div class="sheet">
		<h1>{{ $group->name }} — {{ $season['label'] }} season report</h1>
		<p class="subtitle">
			Covers {{ $season['start']->format('d.m.Y') }} – {{ $season['end']->format('d.m.Y') }} ·
			generated {{ $generatedAt->format('d.m.Y H:i') }}
		</p>

		<h2>Return checklist — still checked out</h2>
		@if($checklist->isEmpty())
			<p class="empty">Everything has been returned. Nothing to collect.</p>
		@else
			<table>
				<thead>
					<tr><th>Code</th><th>Costume</th><th>Held by</th><th class="num">Days out</th></tr>
				</thead>
				<tbody>
					@foreach($checklist as $row)
						<tr>
							<td>{{ $row['code'] }}</td>
							<td>{{ $row['costume'] }}</td>
							<td>{{ $row['who'] }}</td>
							<td class="num {{ $row['days'] >= 30 ? 'days-high' : '' }}">{{ $row['days'] }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif

		<h2>Costume usage this season</h2>
		@if($usage->isEmpty())
			<p class="empty">No costumes in inventory yet.</p>
		@else
			<table>
				<thead>
					<tr><th>Costume</th><th class="num">Items</th><th class="num">Checkouts</th><th class="num">Students</th><th class="num">Avg. days held</th></tr>
				</thead>
				<tbody>
					@foreach($usage as $row)
						<tr>
							<td>{{ $row['name'] }}</td>
							<td class="num">{{ $row['items'] }}</td>
							<td class="num">{{ $row['checkouts'] }}</td>
							<td class="num">{{ $row['students'] }}</td>
							<td class="num">{{ $row['avg_days'] ?? '—' }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>
</body>
</html>
