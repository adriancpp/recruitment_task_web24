<?php

namespace App\Http\Requests;

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
            'file' => ['required', 'file', 'max:51200'],
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
