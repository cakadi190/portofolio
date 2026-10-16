{!! __('mail.hello_text', ['name' => $reply->contactMessage->name]) !!}

{!! \App\Services\PlainTextConverter::fromHtml($reply->body) !!}
@if ($attachmentNames !== [])

{!! __('mail.reply.attachments') !!}
{!! __('mail.reply.attachments_label') !!} {!! implode(', ', $attachmentNames) !!}
@endif

--
{!! __('mail.reply.previous', ['reason' => $reply->contactMessage->reason->label()]) !!}
{!! \App\Services\PlainTextConverter::fromHtml($reply->contactMessage->message) !!}
