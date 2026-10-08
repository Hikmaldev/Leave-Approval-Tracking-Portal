<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignSupervisorRequest extends FormRequest
{
    /**
     * ACT-15: only HR/Admin can assign an employee's direct supervisor.
     */
    public function authorize(): bool
    {
        return $this->user()?->isHrAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'supervisor_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * ACT-15 failure result: reject assignments that would make the user their
     * own supervisor or create a circular reporting relationship.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->route('user');

                if ($user === null) {
                    return;
                }

                $supervisorId = (int) $this->input('supervisor_id');

                if ($supervisorId === $user->id) {
                    $validator->errors()->add(
                        'supervisor_id',
                        'A user cannot be their own supervisor.',
                    );

                    return;
                }

                // Walk up the proposed reporting chain; if we meet the user
                // again, the assignment would create a cycle. The visited list
                // also guards against pre-existing bad data looping forever.
                $cursor = User::find($supervisorId);
                $visited = [];

                while ($cursor !== null && ! in_array($cursor->id, $visited, true)) {
                    $visited[] = $cursor->id;

                    if ($cursor->id === $user->id) {
                        $validator->errors()->add(
                            'supervisor_id',
                            'This assignment would create a circular reporting relationship.',
                        );

                        return;
                    }

                    $cursor = $cursor->supervisor;
                }
            },
        ];
    }
}
