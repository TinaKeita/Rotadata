<!DOCTYPE html>
<html lang="lv">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<title>Piekļuve Rotadata</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f2ee; -webkit-font-smoothing:antialiased;">

	{{-- priekšskatījuma teksts iesūtnē --}}
	<div style="display:none; max-height:0; overflow:hidden; opacity:0;">
		Tavs konts ir izveidots. Pagaidu parole ir e-pastā zemāk.
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
								Sveiki, {{ $user->name }}!
							</p>
							<p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555555;">
								@if($groupName)
									Skolotājs ir izveidojis tev kontu grupai <strong>{{ $groupName }}</strong>, lai pārvaldītu tērpu inventāru.
								@else
									Skolotājs ir izveidojis tev kontu tērpu inventāra pārvaldībai.
								@endif
							</p>

							{{-- pieslēgšanās dati --}}
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f2ee; border-left:3px solid #748873; border-radius:6px;">
								<tr>
									<td style="padding:20px 22px;">
										<p style="margin:0 0 4px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">E-pasts</p>
										<p style="margin:0 0 16px; font-size:15px; color:#2f3a2f;">{{ $user->email }}</p>

										<p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a8a8a;">Pagaidu parole</p>
										<p style="margin:0; padding:10px 14px; background-color:#ffffff; border:1px solid #e0ddd5; border-radius:6px; font-family:'Courier New', Courier, monospace; font-size:18px; font-weight:700; letter-spacing:2px; color:#2f3a2f;">{{ $password }}</p>
									</td>
								</tr>
							</table>

							<p style="margin:16px 0 28px; font-size:13px; line-height:1.6; color:#777777;">
								Šī parole derīga tikai pirmajai pieslēgšanās reizei. Pēc pieslēgšanās tevi lūgs izveidot savu paroli.
							</p>

							{{-- poga --}}
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									<td style="background-color:#4f6150; border-radius:8px;">
										<a href="{{ route('login') }}" target="_blank"
											style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
											Pieslēgties
										</a>
									</td>
								</tr>
							</table>

							<p style="margin:28px 0 0; font-size:13px; line-height:1.6; color:#999999;">
								Ja poga nedarbojas, atver šo saiti:<br>
								<a href="{{ route('login') }}" style="color:#4f6150; word-break:break-all;">{{ route('login') }}</a>
							</p>
						</td>
					</tr>

					{{-- kājene --}}
					<tr>
						<td style="padding:20px 32px; background-color:#faf9f6; border-top:1px solid #eeece6;">
							<p style="margin:0; font-size:12px; line-height:1.6; color:#a0a0a0;">
								Ja negaidīji šo e-pastu, vari to droši ignorēt.<br>
								Rotadata — tērpu inventāra pārvaldība
							</p>
						</td>
					</tr>

				</table>

			</td>
		</tr>
	</table>

</body>
</html>
