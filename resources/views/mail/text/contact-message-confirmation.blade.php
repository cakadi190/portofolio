Halo, {!! $contactMessage->name !!}.

Terima kasih sudah menghubungi {!! config('app.name') !!}. Pesan Anda dengan keperluan "{!! $contactMessage->reason->label() !!}" sudah kami terima dan akan segera diproses. Balasan akan dikirim ke alamat email ini.

Salinan pesan Anda:
{!! \App\Services\PlainTextConverter::fromHtml($contactMessage->message) !!}

--
{!! config('app.name') !!} — {!! config('app.url') !!}
