<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; -webkit-text-size-adjust:100%;">
  {{-- Preheader (hidden preview text) --}}
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $lines[0] ?? $heading }}</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%;">

          {{-- Header --}}
          <tr>
            <td style="padding:8px 8px 20px 8px;">
              <span style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:18px; font-weight:700; color:#0f172a; letter-spacing:-0.2px;">
                {{ $orgName }}
              </span>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background-color:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:1.3; font-weight:700; color:#0f172a;">
                      {{ $heading }}
                    </h1>

                    @if (!empty($greetingName))
                      <p style="margin:0 0 14px 0; font-size:15px; line-height:1.6; color:#334155;">Hi {{ $greetingName }},</p>
                    @endif

                    @foreach ($lines as $line)
                      <p style="margin:0 0 14px 0; font-size:15px; line-height:1.6; color:#334155;">{!! e($line) !!}</p>
                    @endforeach

                    @if (!empty($actionText) && !empty($actionUrl))
                      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 8px 0;">
                        <tr>
                          <td style="border-radius:10px; background-color:#2563eb;">
                            <a href="{{ $actionUrl }}" target="_blank"
                               style="display:inline-block; padding:12px 22px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:10px;">
                              {{ $actionText }}
                            </a>
                          </td>
                        </tr>
                      </table>
                      <p style="margin:6px 0 0 0; font-size:12px; line-height:1.5; color:#94a3b8; word-break:break-all;">
                        Or paste this link into your browser:<br>
                        <a href="{{ $actionUrl }}" target="_blank" style="color:#2563eb; text-decoration:underline;">{{ $actionUrl }}</a>
                      </p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="padding:20px 8px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
              @if (!empty($footnote))
                <p style="margin:0 0 8px 0; font-size:12px; line-height:1.5; color:#94a3b8;">{{ $footnote }}</p>
              @endif
              <p style="margin:0; font-size:12px; line-height:1.5; color:#94a3b8;">
                You're receiving this because email notifications are enabled for your {{ $orgName }} account.
                Manage these in Settings &rarr; Notifications.
              </p>
              <p style="margin:8px 0 0 0; font-size:12px; color:#cbd5e1;">&copy; {{ date('Y') }} {{ $orgName }}. All rights reserved.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
