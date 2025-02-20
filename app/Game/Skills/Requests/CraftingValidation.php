<?php

namespace App\Game\Skills\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CraftingValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'item_to_craft'   => 'required|integer',
            'type'            => 'required|in:weapon,hammer,bow,stave,armour,spell,ring,artifact',
        ];
    }

    public function messages() {
        return [
            'item_to_craft.required' => 'What item are you trying to craft?',
            'type.required'          => 'Missing type.',
            'type.in'                => 'Invalid input.',
        ];
    }
}
