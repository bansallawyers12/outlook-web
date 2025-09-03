<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Label;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(): array
    {
        return Label::forUser(auth()->id() ?? 1)->active()->orderBy('type')->orderBy('name')->get()->toArray();
    }

    public function apply(Request $request): array
    {
        $data = $request->validate([
            'email_id' => ['required', 'integer', 'exists:emails,id'],
            'label_id' => ['required', 'integer', 'exists:labels,id'],
        ]);

        $email = Email::findOrFail($data['email_id']);
        $label = Label::forUser(auth()->id() ?? 1)->findOrFail($data['label_id']);
        $email->labels()->syncWithoutDetaching([$label->id]);

        return ['success' => true];
    }

    public function remove(Request $request): array
    {
        $data = $request->validate([
            'email_id' => ['required', 'integer', 'exists:emails,id'],
            'label_id' => ['required', 'integer', 'exists:labels,id'],
        ]);

        $email = Email::findOrFail($data['email_id']);
        $label = Label::forUser(auth()->id() ?? 1)->findOrFail($data['label_id']);
        $email->labels()->detach($label->id);

        return ['success' => true];
    }
}


