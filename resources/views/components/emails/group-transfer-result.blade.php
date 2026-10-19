<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<title>Rotadata</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f2ee; -webkit-font-smoothing:antialiased;">

	{{-- priekšskatījuma teksts iesūtnē --}}
	<div style="display:none; max-height:0; overflow:hidden; opacity:0;">
		An update on your group handover.
	</div>

	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f2ee;">
		<tr>
			<td align="center" style="padding:32px 16px;">

				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:14px; overflow:hidden; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

					{{-- galvene --}}
					<tr>
						<td style="background-color:#4f6150; padding:22px 32px;">
							<span style="font-size:20px; font-weight:700; letter-spacing:0.5px; color:#ffffff;">Rotadata</span>
						</td>
					</tr>

					{{-- saturs --}}
					<tr>
						<td style="padding:32px;">
							@php
								$buttonUrl = route('login');
								$buttonLabel = 'Open Rotadata';
							@endphp
							<p style="margin:0 0 4px; font-size:18px; font-weight:600; color:#2f3a2f;">
								Hi {{ $transfer->fromUser->name }},
							</p>
							@if($accepted)
								<p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#555555;">
									<strong>{{ $transfer->toUser->name }}</strong> accepted your request and is now the teacher of
									<strong>{{ $transfer->group->name }}</strong>. All its students, costumes, concerts and history went with it.
								</p>
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									You no longer manage this group.
								</p>
							@else
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									<strong>{{ $transfer->toUser->name }}</strong> declined taking over <strong>{{ $transfer->group->name }}</strong>.
									Nothing has changed, and the group is still yours. You can send a request to someone else from Group settings.
								</p>
							@endif
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									<td style="background-color:#4f6150; border-radius:8px;">
										<a href="{{ $buttonUrl }}" target="_blank"
											style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
											{{ $buttonLabel }}
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
								Questions? Just reply to this email.<br>
								Rotadata — costume inventory management
							</p>
						</td>
					</tr>

				</table>

			</td>
		</tr>
	</table>

</body>
</html>
