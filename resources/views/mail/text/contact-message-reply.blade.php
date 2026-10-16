Halo, {!! $reply->contactMessage->name !!}.

{!! \App\Services\PlainTextConverter::fromHtml($reply->body) !!}
@if ($attachmentNames !== [])

Bersama email ini kami menyertakan lampiran. Mohon berkenan membuka berkas lampiran yang kami kirimkan untuk informasi selengkapnya.
Lampiran: {!! implode(', ', $attachmentNames) !!}
@endif

--
Pesan Anda sebelumnya ({!! $reply->contactMessage->reason->label() !!}):
{!! \App\Services\PlainTextConverter::fromHtml($reply->contactMessage->message) !!}
