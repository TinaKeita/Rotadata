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
		@switch($variant)
			@case('deactivated') Your account is inactive. Your teacher can restore it until {{ $purgeDate }}. @break
			@case('removed') Your account and your other groups aren't affected. @break
			@case('restored') The group “{{ $groupName }}” is back. You can sign in again. @break
		@endswitch
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

							@if($variant === 'deactivated')
								<p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#555555;">
									Your teacher deleted the group <strong>“{{ $groupName }}”</strong>. It was your only
									group, so your Rotadata account is now inactive — you can't sign in for the moment.
								</p>

								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6ede1; border-left:3px solid #D1A980; border-radius:6px;">
									<tr>
										<td style="padding:16px 20px;">
											<p style="margin:0; font-size:13.5px; line-height:1.6; color:#6f6350;">
												If this was a mistake, your teacher can restore the group and your account
												until <strong style="color:#4a3f2c;">{{ $purgeDate }}</strong>. After that date,
												the account and all its data are permanently deleted.
											</p>
										</td>
									</tr>
								</table>

							@elseif($variant === 'removed')
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									Your teacher deleted the group <strong>“{{ $groupName }}”</strong>. You're in other
									groups too, so your account keeps working as normal — the only change is that this
									group and its costumes are no longer available.
								</p>

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

							@else
								<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
									Good news — the group <strong>“{{ $groupName }}”</strong> has been restored and your
									account is active again. All costumes, assignments and history were kept.
								</p>

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
							@endif

							<p style="margin:24px 0 0; font-size:12.5px; line-height:1.6; color:#9a9a92;">
								This is an automated message. Replies aren't monitored.
							</p>
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
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
