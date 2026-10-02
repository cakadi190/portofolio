<!doctype html>
<html lang="id">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.6;">
    <p>Halo {{ $contactMessage->name }},</p>
    <p>Terima kasih sudah menghubungi {{ config('app.name') }}. Pesan Anda dengan keperluan <strong>{{ $contactMessage->reason->label() }}</strong> sudah kami terima dan akan segera diproses.</p>
    <p>Berikut salinan pesan Anda:</p>
    <blockquote style="border-left: 3px solid #ced4da; margin: 0; padding-left: 12px;">
        {!! $contactMessage->message !!}
    </blockquote>
    <p>Salam,<br>{{ config('app.name') }}</p>
</body>
</html>
