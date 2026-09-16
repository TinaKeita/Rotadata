<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<title>Rotadata</title>
	<style>
		/* paroles rādīšanas/slēpšanas pārslēgs – checkbox hack, darbojas klientos ar CSS :checked atbalstu */
		#pw-toggle:checked ~ .pw-wrap .pw-masked { display: none !important; }
		#pw-toggle:checked ~ .pw-wrap .pw-value { display: inline !important; }
		#pw-toggle:checked ~ .pw-wrap .label-show { display: none !important; }
		#pw-toggle:checked ~ .pw-wrap .label-hide { display: inline !important; }
	</style>
</head>
<body style="margin:0; padding:0; background-color:#f4f2ee; -webkit-font-smoothing:antialiased;">

	{{-- priekšskatījuma teksts iesūtnē --}}
	<div style="display:none; max-height:0; overflow:hidden; opacity:0;">
		Your teacher reset your password. Your new temporary password is in the email below.
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
								@if($groupName)
									Your teacher reset your password for <strong>{{ $groupName }}</strong> on Rotadata. Your old password no longer works.
								@else
									Your teacher reset your Rotadata password. Your old password no longer works.
								@endif
							</p>

							{{-- pieslēgšanās dati --}}
							<!--[if mso]>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f2ee; border-left:3px solid #748873; border-radius:6px;">
								<tr>
									<td style="padding:20px 22px;">
										<p style="margin:0 0 4px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">Email</p>
										<p style="margin:0 0 16px; font-size:15px; color:#2f3a2f;">{{ $user->email }}</p>

										<p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">Temporary password</p>
										<p style="margin:0; padding:10px 14px; background-color:#ffffff; border:1px solid #e0ddd5; border-radius:6px; font-family:'Courier New', Courier, monospace; font-size:18px; font-weight:700; letter-spacing:2px; color:#2f3a2f;">{{ $password }}</p>
									</td>
								</tr>
							</table>
							<![endif]-->
							<!--[if !mso]><!-->
							<input type="checkbox" id="pw-toggle" style="display:none;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="pw-wrap" style="background-color:#f4f2ee; border-left:3px solid #748873; border-radius:6px;">
								<tr>
									<td style="padding:20px 22px;">
										<p style="margin:0 0 4px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">Email</p>
										<p style="margin:0 0 16px; font-size:15px; color:#2f3a2f;">{{ $user->email }}</p>

										<p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">Temporary password</p>
										<table role="presentation" cellpadding="0" cellspacing="0">
											<tr>
												<td style="padding:10px 14px; background-color:#ffffff; border:1px solid #e0ddd5; border-radius:6px; font-family:'Courier New', Courier, monospace; font-size:18px; font-weight:700; letter-spacing:2px; color:#2f3a2f;">
													<span class="pw-masked">{{ str_repeat('•', strlen($password)) }}</span><span class="pw-value" style="display:none;">{{ $password }}</span>
												</td>
												<td style="padding:10px 0 10px 10px;">
													<label for="pw-toggle" style="cursor:pointer; display:inline-block; padding:9px 16px; border:1px solid #748873; border-radius:6px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; font-weight:600; color:#4f6150; white-space:nowrap;">
														<span class="label-show">Reveal</span><span class="label-hide" style="display:none;">Hide</span>
													</label>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
							<![endif]-->

							<p style="margin:16px 0 28px; font-size:13px; line-height:1.6; color:#777777;">
								This password only works for your next sign-in. After that you'll be asked to set your own.
							</p>

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
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
								Didn't expect this? Someone with access to your teacher's account reset it — tell your
								teacher right away.<br>
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
