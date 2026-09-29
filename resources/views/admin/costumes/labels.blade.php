<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $costume->name }} — QR labels</title>
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

		.toolbar a,
		.toolbar button {
			font: inherit;
			font-size: .82rem;
			font-weight: 600;
			text-decoration: none;
			padding: .4rem .8rem;
			border-radius: 999px;
			border: 1px solid #D9D5CD;
			background: #fff;
			color: #1B1B19;
			cursor: pointer;
		}

		.toolbar .primary { background: #2F5D46; border-color: #2F5D46; color: #FBFAF8; }
		.toolbar .active { background: #EDF1EC; border-color: #2F5D46; color: #2F5D46; }
		.toolbar .group { display: inline-flex; gap: .35rem; align-items: center; }
		.toolbar .label-text { font-family: 'IBM Plex Mono', monospace; font-size: .7rem; font-weight: 400; text-transform: uppercase; letter-spacing: .1em; color: #6E6A61; }
		.toolbar .spacer { flex: 1 1 auto; }

		.sheet { padding: 8mm; }

		.grid {
			display: grid;
			grid-template-columns: repeat({{ $columns }}, 1fr);
			gap: 4mm;
		}

		.tag {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 2mm;
			padding: 4mm 2mm;
			text-align: center;
			border: 1px dashed #D9D5CD;
			border-radius: 4px;
			page-break-inside: avoid;
			break-inside: avoid;
		}

		.tag svg { width: 26mm; height: 26mm; }
		.tag .code { font-family: 'IBM Plex Mono', monospace; font-weight: 500; font-size: 10pt; letter-spacing: .02em; }
		.tag .name { font-size: 7.5pt; color: #6E6A61; line-height: 1.2; }

		.empty { padding: 3rem 1rem; text-align: center; color: #6E6A61; }

		@media print {
			body { background: #fff; }
			.toolbar { display: none; }
			.sheet { padding: 0; }
			.tag { border-color: #E7E4DE; }
			@page { size: A4; margin: 10mm; }
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
		<a href="{{ route('admin.costumes.show', $costume) }}">&larr; Back to inventory</a>

		<span class="spacer"></span>

		<span class="group">
			<span class="label-text">Show</span>
			<a class="{{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.costumes.labels', ['costume' => $costume, 'cols' => $columns]) }}">All</a>
			<a class="{{ $filter === 'available' ? 'active' : '' }}" href="{{ route('admin.costumes.labels', ['costume' => $costume, 'filter' => 'available', 'cols' => $columns]) }}">Available</a>
			<a class="{{ $filter === 'assigned' ? 'active' : '' }}" href="{{ route('admin.costumes.labels', ['costume' => $costume, 'filter' => 'assigned', 'cols' => $columns]) }}">Assigned</a>
		</span>

		<span class="group">
			<span class="label-text">Columns</span>
			@foreach ([2, 3, 4] as $option)
				<a class="{{ $columns === $option ? 'active' : '' }}" href="{{ route('admin.costumes.labels', ['costume' => $costume, 'filter' => $filter === 'all' ? null : $filter, 'cols' => $option]) }}">{{ $option }}</a>
			@endforeach
		</span>
	</div>

	<div class="sheet">
		@if ($items->isEmpty())
			<p class="empty">No items match this filter.</p>
		@else
			<div class="grid">
				@foreach ($items as $item)
					<div class="tag">
						{!! QrCode::size(120)->generate(url('/scan/'.$item->qr_code)) !!}
						<span class="code">{{ $item->code }}</span>
						<span class="name">{{ $costume->name }}</span>
					</div>
				@endforeach
			</div>
		@endif
	</div>
</body>
</html>
