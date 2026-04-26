<?php

namespace App\Http\Requests;

use App\Support\ImportFileLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.ImportFileLimits::MAX_KILOBYTES,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());

                    if (! in_array($extension, ['csv', 'json', 'xml'], true)) {
                        $fail('The file must be a csv, json, or xml file.');

                        return;
                    }

                    $mime = $this->normalizedMimeType((string) $value->getMimeType());
                    if ($mime === '') {
                        $mime = $this->normalizedMimeType((string) $value->getClientMimeType());
                    }

                    // finfo often returns text/plain for JSON/XML without declaration or small payloads;
                    // Windows CSV is frequently application/vnd.ms-excel.
                    $allowedByExtension = [
                        'csv' => [
                            'text/csv', 'text/plain', 'application/csv', 'application/octet-stream',
                            'application/vnd.ms-excel',
                        ],
                        'json' => [
                            'application/json', 'text/json', 'application/octet-stream', 'text/plain',
                        ],
                        'xml' => [
                            'application/xml', 'text/xml', 'application/octet-stream', 'text/plain',
                        ],
                    ];

                    if (! in_array($mime, $allowedByExtension[$extension], true)) {
                        $fail('The file MIME type is not allowed for this file type.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = (int) floor(ImportFileLimits::MAX_KILOBYTES / 1024);

        return [
            'file.required' => 'Please attach a transaction file.',
            'file.file' => 'The upload must be a valid file.',
            'file.max' => "The file may not be greater than {$maxMb} MB.",
        ];
    }

    private function normalizedMimeType(string $mime): string
    {
        $mime = strtolower(trim($mime));
        if ($mime === '') {
            return '';
        }

        return trim(explode(';', $mime, 2)[0]);
    }
}
