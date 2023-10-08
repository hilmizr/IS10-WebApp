<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'address' => ['string', 'max:255'],
            'phone' => ['string', 'max:255'],
            'university' => ['string', 'max:255'],
            'major' => ['string', 'max:255'],
            'date_of_birth' => ['date'],
            'id_number' => ['string', 'max:255'],
            'student_id_number' => ['string', 'max:255'],

            // The following fields are not required
            'name' => [], // You can remove these lines, or simply leave them as empty arrays.
            'address' => [],
            'phone' => [],
            'university' => [],
            'major' => [],
            'date_of_birth' => [],
            'id_number' => [],
            'student_id_number' => []

        ];
    }
}
