<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $group->name }} — {{ $season['label'] }} season report</title>
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=newsreader:400|public-sans:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />
	<style>
		* { box-sizing: border-box; }

		body {
			margin: 0;
			font-family: 'Public Sans', system-ui, -apple-system, sans-serif;
			color: #1B1B19;
			background: #FBFAF8;
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
			border-bottom: 1px solid #E7E4DE;
		}

		.toolbar a, .toolbar button, .toolbar select {
			font: inherit;
			font-size: .82rem;
			font-weight: 500;
			text-decoration: none;
			padding: .4rem .9rem;
			border-radius: 999px;
			border: 1px solid #D9D5CD;
			background: #fff;
			color: #1B1B19;
			cursor: pointer;
		}

		.toolbar .primary { background: #2F5D46; border-color: #2F5D46; color: #FBFAF8; }
		.toolbar .spacer { flex: 1 1 auto; }

		.sheet { max-width: 960px; margin: 0 auto; padding: 12mm 8mm; }

		h1 { font-family: 'Newsreader', Georgia, serif; font-weight: 400; font-size: 2rem; letter-spacing: -.02em; margin: 0 0 .15rem; }
		.eyebrow { font-family: 'IBM Plex Mono', monospace; font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; color: #6E6A61; margin: 0 0 .4rem; }
		.subtitle { color: #6E6A61; font-size: .9rem; margin: 0 0 1.5rem; }

		h2 {
			font-family: 'IBM Plex Mono', monospace;
			font-weight: 400;
			font-size: .72rem;
			text-transform: uppercase;
			letter-spacing: .1em;
			color: #2F5D46;
			margin: 2rem 0 .6rem;
			border-bottom: 1px solid #E7E4DE;
			padding-bottom: .35rem;
		}
		.hint { color: #6E6A61; font-size: .78rem; margin: -.2rem 0 .6rem; }

		table { width: 100%; border-collapse: collapse; font-size: .82rem; }
		th, td { text-align: left; padding: .4rem .5rem; border-bottom: 1px solid #F4F2EE; vertical-align: top; }
		th { color: #6E6A61; font-family: 'IBM Plex Mono', monospace; font-weight: 400; font-size: .66rem; text-transform: uppercase; letter-spacing: .08em; }
		td.num, th.num { text-align: right; white-space: nowrap; }
		.mono { font-family: 'IBM Plex Mono', monospace; font-size: .78rem; }
		.soft { color: #6E6A61; }
		.late { color: #8A4B33; font-weight: 600; }
		.good { color: #2F5D46; }
		.small { font-size: .74rem; }

		.empty { padding: 1.2rem 0; text-align: center; color: #6E6A61; font-size: .85rem; }

		section { break-inside: avoid-page; }
		tr { break-inside: avoid; }

		@media print {
			body { background: #fff; }
			.toolbar { display: none; }
			.sheet { padding: 0; max-width: none; }
			@page { size: A4; margin: 14mm; }
		}
	</style>
</head>
<body>
	{{-- rīkjosla: drukāšana, sezonas izvēle un pilnā žurnāla lejupielāde --}}
	<div class="toolbar">
		<button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
		<form method="GET" action="{{ route('admin.season-report.show') }}" style="margin:0">
			<select name="season" onchange="this.form.submit()" aria-label="Season">
				@foreach($seasons as $s)
					<option value="{{ $s['year'] }}" @selected($s['year'] === $season['year'])>Season {{ $s['label'] }}{{ $s['current'] ? ' (current)' : '' }}</option>
				@endforeach
			</select>
		</form>
		<span class="spacer"></span>
		<a href="{{ route('admin.dashboard') }}">&larr; Back to dashboard</a>
	</div>

	<div class="sheet">
		<p class="eyebrow">Season report · {{ $season['label'] }}</p>
		<h1>{{ $group->name }}</h1>
		<p class="subtitle">
			Covers {{ $season['start']->format('d.m.Y') }} – {{ $season['end']->format('d.m.Y') }}
			@if($season['current']) · state as of today, {{ $asOf->format('d.m.Y') }} @else · state at the end of the season @endif
			· teacher {{ $group->admin?->name }} · generated {{ $generatedAt->format('d.m.Y H:i') }}
		</p>

		{{-- kas vēl jāsavāc --}}
		<section>
			<h2>Still to collect — by student</h2>
			<p class="hint">Everything not returned{{ $season['current'] ? ' yet' : ' by the end of the season' }}. Students who left still have to bring items back.</p>
			@if($outstanding->isEmpty())
				<p class="empty">Everything has been returned. Nothing to collect.</p>
			@else
				<table>
					<thead>
						<tr><th>Student</th><th>Contact</th><th>Items</th><th class="num">Longest out</th></tr>
					</thead>
					<tbody>
						@foreach($outstanding as $o)
							<tr>
								<td>
									<strong>{{ $o['name'] }}</strong>
									<div class="small soft">{{ $o['set'] ? 'Set: '.$o['set'].' · ' : '' }}<span class="{{ $o['status'] !== 'in group' ? 'late' : '' }}">{{ $o['status'] }}</span></div>
								</td>
								<td class="small">{{ $o['email'] ?? '—' }}</td>
								<td>
									@foreach($o['items'] as $i)
										<div><span class="mono">{{ $i['code'] }}</span> {{ $i['costume'] }} <span class="small soft">since {{ $i['since']->format('d.m.Y') }}</span></div>
									@endforeach
								</td>
								<td class="num {{ $o['longest'] >= 30 ? 'late' : '' }}">{{ $o['longest'] }} days</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>

		{{-- inventārs: kas ir, kas ārā, kas brīvs --}}
		<section>
			<h2>Inventory</h2>
			@if($inventory->isEmpty())
				<p class="empty">No costumes in inventory yet.</p>
			@else
				<table>
					<thead>
						<tr><th>Costume</th><th>Set</th><th class="num">Items</th><th class="num">Out</th><th class="num">Free</th></tr>
					</thead>
					<tbody>
						@foreach($inventory as $row)
							<tr>
								<td>{{ $row['name'] }}</td>
								<td class="soft">{{ $row['set'] ?? 'shared' }}</td>
								<td class="num">{{ $row['items'] }}</td>
								<td class="num {{ $row['out'] ? 'late' : '' }}">{{ $row['out'] }}</td>
								<td class="num">{{ $row['free'] }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>

		{{-- koncerti --}}
		<section>
			<h2>Concerts</h2>
			<p class="hint">Readiness on the day is rebuilt from the item history: who held every costume they needed when the concert started. It uses today's students and sets.</p>
			@if($concerts->isEmpty())
				<p class="empty">No concerts this season.</p>
			@else
				<table>
					<thead>
						<tr><th>Date</th><th>Concert</th><th>Costumes</th><th class="num">Performing</th><th class="num">Ready</th></tr>
					</thead>
					<tbody>
						@foreach($concerts as $c)
							@php $r = $c['readiness']; @endphp
							<tr>
								<td class="mono">{{ $c['event']->starts_at->format('d.m.Y H:i') }}@unless($c['held'])<div class="small soft">upcoming</div>@endunless</td>
								<td>
									<strong>{{ $c['event']->title }}</strong>
									@if($c['event']->location)<div class="small soft">{{ $c['event']->location }}</div>@endif
									@if($c['notReady']->isNotEmpty() && $r['hasCostumes'])
										<div class="small late" style="margin-top:.2rem">
											Not ready: {{ $c['notReady']->take(8)->map(fn ($n) => $n['student']->name.($n['no_set'] ? ' (no set)' : ' ('.$n['missingText'].')'))->implode('; ') }}{{ $c['notReady']->count() > 8 ? ' and '.($c['notReady']->count() - 8).' more' : '' }}
										</div>
									@endif
								</td>
								<td class="small">
									{{ $c['event']->costumes->pluck('name')->implode(', ') ?: '—' }}
									@if($c['extras'])<div class="soft">+ {{ $c['extras'] }} extra for soloists</div>@endif
								</td>
								<td class="num">{{ $r['total'] }}@if($r['absentCount'])<div class="small soft">{{ $r['absentCount'] }} not performing</div>@endif</td>
								<td class="num">
									@if($r['hasCostumes'])
										<span class="{{ $r['ready'] === $r['total'] ? 'good' : 'late' }}">{{ $r['ready'] }}/{{ $r['total'] }}</span>
										<div class="small soft">{{ $r['percent'] }}%{{ $c['held'] ? ' on the day' : ' now' }}</div>
									@else
										<span class="soft">no costumes</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>

		{{-- komplekti --}}
		@if($sets->isNotEmpty())
			<section>
				<h2>Sets</h2>
				<table>
					<thead><tr><th>Set</th><th class="num">Students</th><th class="num">Costumes</th></tr></thead>
					<tbody>
						@foreach($sets as $set)
							<tr><td>{{ $set['name'] }}</td><td class="num">{{ $set['students'] }}</td><td class="num">{{ $set['costumes'] }}</td></tr>
						@endforeach
					</tbody>
				</table>
			</section>
		@endif

	</div>
</body>
</html>
