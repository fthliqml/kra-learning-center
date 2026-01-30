<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Survey Level 1</title>
</head>

<body
  style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
    <tr>
      <td style="padding: 40px 20px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
          style="margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
          {{-- Header --}}
          <tr>
            <td
              style="padding: 32px 40px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 12px 12px 0 0;">
              <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                🎓 KRA Learning Center
              </h1>
            </td>
          </tr>

          {{-- Content --}}
          <tr>
            <td style="padding: 40px;">
              <p style="margin: 0 0 24px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                Halo <strong>{{ $employee->name }}</strong>,
              </p>

              <p style="margin: 0 0 24px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                Survey Level 1 untuk training berikut telah dibuka dan menunggu tanggapan Anda:
              </p>

              {{-- Training Info Card --}}
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                style="background-color: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6; margin-bottom: 24px;">
                <tr>
                  <td style="padding: 20px;">
                    <p style="margin: 0 0 12px 0; font-size: 14px; color: #6b7280;">
                      📚 <strong style="color: #1f2937;">Training</strong>
                    </p>
                    <p style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1e3a8a;">
                      {{ $training->name }}
                    </p>

                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;">
                      📅 <strong>Tanggal:</strong>
                      @if($training->start_date && $training->end_date)
                        {{ $training->start_date->format('d M Y') }}
                        @if($training->start_date->ne($training->end_date))
                          - {{ $training->end_date->format('d M Y') }}
                        @endif
                      @else
                        -
                      @endif
                    </p>

                    @php
                      $trainerNames = $training->sessions()
                        ->with('trainer.user')
                        ->get()
                        ->pluck('trainer')
                        ->filter()
                        ->map(fn($t) => $t->name ?? $t->user?->name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(', ');
                    @endphp
                    @if($trainerNames)
                      <p style="margin: 0; font-size: 14px; color: #6b7280;">
                        👨‍🏫 <strong>Trainer:</strong> {{ $trainerNames }}
                      </p>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin: 0 0 32px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                Mohon segera isi survey untuk memberikan feedback terhadap training yang telah Anda ikuti.
              </p>

              {{-- CTA Button --}}
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                <tr>
                  <td style="border-radius: 8px; background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);">
                    <a href="{{ $surveyUrl }}" target="_blank"
                      style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                      Isi Survey Sekarang
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin: 32px 0 0 0; font-size: 16px; color: #374151; line-height: 1.6;">
                Terima kasih atas partisipasi Anda.
              </p>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td
              style="padding: 24px 40px; background-color: #f8fafc; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
              <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-align: center;">
                © {{ date('Y') }} KRA Learning Center
              </p>
              <p style="margin: 0; font-size: 12px; color: #9ca3af; text-align: center;">
                Email ini dikirim secara otomatis, mohon tidak membalas email ini.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>