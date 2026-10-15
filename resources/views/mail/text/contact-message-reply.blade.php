Halo, {!! $reply->contactMessage->name !!}.

{!! \App\Services\PlainTextConverter::fromHtml($reply->body) !!}
@if ($attachmentNames !== [])

Lampiran: {!! implode(', ', $attachmentNames) !!}
@endif

--
Pesan Anda sebelumnya ({!! $reply->contactMessage->reason->label() !!}):
{!! \App\Services\PlainTextConverter::fromHtml($reply->contactMessage->message) !!}
