<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ShowCard extends Component
{
    /**
     * Create a new component instance.
     */
    public $show;
    public $genres;

    public function __construct($show, $genres)
    {
        $this->show = $show;
        $this->genres = $genres;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.show-card');
    }
}
