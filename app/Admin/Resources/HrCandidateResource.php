<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrCandidate;
use App\Models\HrPosition;
use Illuminate\Database\Eloquent\Model;

class HrCandidateResource extends Resource
{
    public static string $model = HrCandidate::class;

    public static string $slug = 'hr-candidates';

    public static string $label = 'Recruitment';

    public static string $singular = 'Candidate';

    public static string $icon = 'bi-person-lines-fill';

    public static string $group = 'Human resources';

    public function with(): array
    {
        return ['position'];
    }

    public function rowActions(Model $row): array
    {
        return in_array($row->stage, ['selected', 'shortlisted', 'interview'], true) && ! $row->employee_id
            ? [['label' => 'Hire', 'icon' => 'bi-person-plus', 'url' => route('admin.hr.candidates.hire', $row), 'method' => 'post', 'permission' => 'hr-employees.create']]
            : [];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Name')->required()->rules('max:150')->listed(),
            Field::select('position_id', 'Applying for', fn () => HrPosition::where('is_active', true)->orderBy('title')->pluck('title', 'id')->all())->listed(),
            Field::select('stage', 'Stage', HrCandidate::STAGES)->required()->default('applied')->listed(),
            Field::email('email', 'Email'),
            Field::text('phone', 'Phone')->rules('max:40'),
            Field::date('interview_on', 'Interview date'),
            Field::number('rating', 'Rating (1–5)')->rules('min:1|max:5'),
            Field::textarea('notes', 'Notes'),
        ];
    }
}
