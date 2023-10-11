<?php

namespace App\Filament\Pages\Auth;

use App\Models\Department;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                $this->getCompanyFormComponent(),
                $this->getDepartmentFormComponent(),
                $this->getLevelFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getCompanyFormComponent(): Component
    {
        return TextInput::make('company')
            ->label('Company')
            ->required();
    }

    protected function getDepartmentFormComponent(): Component
    {
        return Select::make('department_id')
            ->options(Department::all()->where('title', '!=', 'default')->pluck('title', 'id'))
            ->label('Department')
            ->required();
    }

    protected function getLevelFormComponent(): Component
    {
        return Hidden::make('level_id')
            ->default(1);
    }
}
