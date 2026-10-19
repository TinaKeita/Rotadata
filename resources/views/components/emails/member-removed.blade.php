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
		You've been removed from a group in Rotadata.
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
							<p style="margin:0 0 4px; font-size:18px; font-weight:600; color:#2f3a2f;">
								Hi {{ $user->name }},
							</p>
							<p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#555555;">
								Your teacher removed you from the group <strong>{{ $groupName }}</strong> in Rotadata.
								Any costume items you still had from this group have been marked as returned.
							</p>
							@if($restoreUntil)
								{{-- šī bija vienīgā grupa – konts deaktivizēts, bet atjaunojams --}}
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									This was your only group, so your account is paused. Your teacher can restore it
									until <strong>{{ $restoreUntil }}</strong> if this was a mistake.
								</p>
							@else
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									Your account still works for your other groups. Sign in with your usual password.
								</p>
							@endif

							@unless($restoreUntil)
							{{-- poga --}}
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									<td style="background-color:#4f6150; border-radius:8px;">
										<a href="{{ route('login') }}" target="_blank"
											style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
											Sign in
										</a>
									</td>
								</tr>
							</table>

							<p style="margin:28px 0 0; font-size:13px; line-height:1.6; color:#999999;">
								If the button doesn't work, open this link:<br>
								<a href="{{ route('login') }}" style="color:#4f6150; word-break:break-all;">{{ route('login') }}</a>
							</p>
							@endunless
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
								If you think this was a mistake, talk to your teacher.<br>
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
