<?php

namespace App\Http\Requests;

use App\Support\ImportFileLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

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
            'file' => ['required', 'file', 'max:'.ImportFileLimits::MAX_KILOBYTES],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if (! $file instanceof UploadedFile) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['csv', 'json', 'xml'], true)) {
                $validator->errors()->add('file', 'The file must be a csv, json, or xml file.');
            }
        });
    }
}
