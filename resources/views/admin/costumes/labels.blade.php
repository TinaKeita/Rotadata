<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $costume->name }} — QR labels</title>
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

		.toolbar a,
		.toolbar button {
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
		.toolbar .active { background: #E5E0D8; border-color: #748873; color: #4f6150; }
		.toolbar .group { display: inline-flex; gap: .35rem; align-items: center; }
		.toolbar .label-text { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; }
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
			border: 1px dashed #cbd5e1;
			border-radius: 4px;
			page-break-inside: avoid;
			break-inside: avoid;
		}

		.tag svg { width: 26mm; height: 26mm; }
		.tag .code { font-weight: 700; font-size: 10pt; letter-spacing: .04em; }
		.tag .name { font-size: 7.5pt; color: #6b7280; line-height: 1.2; }

		.empty { padding: 3rem 1rem; text-align: center; color: #6b7280; }

		@media print {
			body { background: #fff; }
			.toolbar { display: none; }
			.sheet { padding: 0; }
			.tag { border-color: #e5e7eb; }
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
