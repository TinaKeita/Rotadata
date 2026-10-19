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
		{{ $invitation->inviter?->name ?? 'A teacher' }} invited you to join {{ $invitation->group->name }}.
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
							<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
								<strong>{{ $invitation->inviter?->name ?? 'A teacher' }}</strong> invited you to join the group
								<strong>{{ $invitation->group->name }}</strong> in Rotadata. Sign in with your usual password
								to accept or decline. Until you accept, you're not part of the group and the teacher can't
								see your account.
							</p>
							<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
								The invitation is open until <strong>{{ $invitation->expires_at->format('d.m.Y') }}</strong>.
							</p>

							{{-- poga --}}
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									<td style="background-color:#4f6150; border-radius:8px;">
										<a href="{{ route('dashboard') }}" target="_blank"
											style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
											See the invitation
										</a>
									</td>
								</tr>
							</table>

							<p style="margin:28px 0 0; font-size:13px; line-height:1.6; color:#999999;">
								If the button doesn't work, open this link:<br>
								<a href="{{ route('dashboard') }}" style="color:#4f6150; word-break:break-all;">{{ route('dashboard') }}</a>
							</p>
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
								If you don't know this teacher, decline the invitation or simply ignore this email.<br>
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
