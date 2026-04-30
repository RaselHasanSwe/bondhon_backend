<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'                => ['required', 'string', 'in:text,image,video,audio,document,voice'],
            'body'                => ['nullable', 'string', 'max:5000', 'required_if:type,text'],
            'file'                => [
                'nullable',
                'file',
                'max:51200',   // 50MB max
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mp3,ogg,wav,m4a,pdf,doc,docx,xls,xlsx,csv',
                'required_unless:type,text',
            ],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:messages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_if'     => 'Message body is required for text messages.',
            'file.required_unless' => 'A file is required for this message type.',
            'file.mimes'           => 'Unsupported file type. Allowed: jpg, png, gif, webp, mp4, mp3, ogg, wav, pdf, doc, docx, xls, xlsx, csv',
            'file.max'             => 'File size cannot exceed 50MB.',
        ];
    }
}

