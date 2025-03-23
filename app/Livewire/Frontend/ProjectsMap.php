<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Models\Project;
use App\Models\City;
use App\Models\State;
use App\Models\Unit;
use App\Models\ProjectType;
use Livewire\Attributes\Title;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Url;
use App\Models\Developer;
use Livewire\Attributes\On;

#[Title('المشاريع')]
class ProjectsMap extends Component
{
    use LivewireAlert;

    public bool $showSideSheet = false;
    public ?Project $selectedProject = null;

    #[Url]
    public $selected_projectTypes = '';

    #[Url]
    public $selected_developer = '';

    #[Url]
    public $price_range = 0;

    #[Url]
    public $selected_cities = null;

    #[Url]
    public $selected_states = null;

    public $states = [];
    public $cities = [];

    public function mount()
    {
        // Load initial data
        $this->cities = City::all();
    }

    #[On('showProject')]
    public function showProject($projectId)
    {
        $this->selectedProject = Project::with('developer', 'units')->find($projectId);
        $this->showSideSheet = true;
        $this->dispatch('side-sheet-updated');
    }

    public function closeSideSheet()
    {
        $this->showSideSheet = false;
        $this->selectedProject = null;
        $this->dispatch('side-sheet-updated');
    }

    public function updatedSelectedCities($value)
    {
        // Fetch states based on the selected city
        if ($value) {
            $city = City::find($value);
            if ($city) {
                $this->states = State::where('id', $city->state_id)->get();
            }
        } else {
            $this->states = [];
        }
    }

    protected function applyFilters($query)
    {
        // Apply project type filter
        if ($this->selected_projectTypes) {
            $query->where('project_type_id', $this->selected_projectTypes);
        }

        // Apply developer filter
        if ($this->selected_developer) {
            $query->where('developer_id', $this->selected_developer);
        }

        // Apply city filter
        if ($this->selected_cities) {
            $query->where('city_id', $this->selected_cities);
        }

        // Apply state filter
        if ($this->selected_states) {
            $query->where('state_id', $this->selected_states);
        }

        // Apply price range filter
        if ($this->price_range) {
            $query->whereHas('units', function ($query) {
                $query->where('price', '<=', $this->price_range);
            });
        }

        return $query;
    }

    public function render()
    {
        // Start with a base query
        $query = Project::with('projectType', 'developer', 'units')->where('status', 1);

        // Apply filters
        $query = $this->applyFilters($query);

        // Get filtered projects
        $projects = $query->get();

        // Dispatch event to update the map
        $this->dispatch('projectsUpdated', ['projects' => $projects]);

        return view('livewire.frontend.projects-map', [
            'developers' => Developer::all(),
            'projectTypes' => ProjectType::where('status', 1)->get(),
            'projects' => $projects,
        ]);
    }
}
