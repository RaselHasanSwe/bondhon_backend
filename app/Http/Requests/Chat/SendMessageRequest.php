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
            'label'               => ['nullable', 'string', 'max:500'],

            // Single file (legacy / audio / voice)
            'file'                => [
                'nullable',
                'file',
                'max:2048',   // 2 MB max
                'mimes:jpg,jpeg,png,gif,webp,mp3,ogg,wav,m4a,pdf,doc,docx',
            ],

            // Multiple files — images (max 10) or documents (max 2)
            'files'               => ['nullable', 'array', 'max:10'],
            'files.*'             => [
                'file',
                'max:2048',    // 2 MB per file
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',
            ],

            'reply_to_message_id' => ['nullable', 'integer', 'exists:messages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_if'     => 'Message body is required for text messages.',
            'files.max'            => 'You can upload at most 10 files at once.',
            'files.*.mimes'        => 'Unsupported file type.',
            'files.*.max'          => 'Each file cannot exceed 2 MB.',
            'file.max'             => 'File size cannot exceed 2 MB.',
        ];
    }
}

