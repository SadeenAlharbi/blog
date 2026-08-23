@php
    // Format the comment time (kept in the view so the notification stays lean).
    $when = optional($comment->created_at)->format('Y/m/d — H:i');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>تعليق جديد على مقالك</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f6f4; font-family:'Tajawal','Segoe UI',Arial,sans-serif; color:#1f2937;">
    <!-- Preheader (hidden) -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ $commenterName }} أضاف تعليقًا على مقالك "{{ $post->title }}".
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f6f4; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e6ece9;">
                    <!-- Brand header -->
                    <tr>
                        <td style="background-color:#074D31; padding:22px 28px; text-align:center;">
                            <span style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:.2px;">منصة المعرفة السعودية</span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 28px 8px 28px; text-align:right;">
                            <p style="margin:0 0 16px 0; font-size:16px; font-weight:700; color:#111827;">مرحبًا {{ $ownerName }},</p>
                            <p style="margin:0 0 20px 0; font-size:15px; line-height:1.9; color:#4b5563;">
                                تمت إضافة تعليق جديد على مقالك:
                            </p>

                            <!-- Article title -->
                            <p style="margin:0 0 20px 0; font-size:18px; font-weight:700; line-height:1.6; color:#074D31;">
                                {{ $post->title }}
                            </p>

                            <!-- Commenter -->
                            <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280;">بواسطة</p>
                            <p style="margin:0 0 20px 0; font-size:15px; font-weight:600; color:#111827;">{{ $commenterName }}</p>

                            <!-- Comment card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px 0;">
                                <tr>
                                    <td style="background-color:#f5f8f6; border:1px solid #e6ece9; border-right:3px solid #0b6b45; border-radius:12px; padding:16px 18px;">
                                        <p style="margin:0; font-size:15px; line-height:1.9; color:#374151; white-space:pre-line;">{{ $commentExcerpt }}</p>
                                    </td>
                                </tr>
                            </table>

                            @if ($when)
                                <p style="margin:0 0 24px 0; font-size:12px; color:#9ca3af;">{{ $when }}</p>
                            @endif

                            <!-- Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 8px 0;">
                                <tr>
                                    <td style="border-radius:12px; background-color:#0b6b45;">
                                        <a href="{{ $url }}" target="_blank"
                                           style="display:inline-block; padding:12px 30px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            عرض التعليق
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:22px 0 0 0; font-size:12px; line-height:1.8; color:#9ca3af;">
                                إذا لم يعمل الزر، انسخ الرابط التالي وافتحه في المتصفح:<br>
                                <a href="{{ $url }}" style="color:#0b6b45; word-break:break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:22px 28px; text-align:center; border-top:1px solid #eef2f0;">
                            <p style="margin:0 0 4px 0; font-size:12px; color:#9ca3af;">
                                &copy; {{ date('Y') }} منصة المعرفة السعودية. جميع الحقوق محفوظة.
                            </p>
                            <p style="margin:0; font-size:12px; color:#c0c7c3;">معرفة موثوقة تواكب رؤية المملكة 2030</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
