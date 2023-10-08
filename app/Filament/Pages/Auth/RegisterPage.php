<?php

namespace App\Filament\Pages\Auth;

use App\Models\Department;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register;

class RegisterPage extends Register
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getDepartmentFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getDepartmentFormComponent(): Component
    {
        return Select::make('department_id')
            ->options(Department::all()->pluck('title', 'id'))
            ->label('Department')
            ->required();
    }
}
