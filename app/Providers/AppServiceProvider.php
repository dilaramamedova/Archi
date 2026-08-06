<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\SocialLink;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('components.layout', function ($view) {
            $view->with([
                'headerMenu' => MenuItem::location('header_main')->roots()->active()->ordered()
                    ->with(['children' => fn ($q) => $q->active()->ordered()])
                    ->get(),

                'megaCatalog' => MenuItem::location('header_mega_catalog')->roots()->active()->ordered()->get(),

                'megaSpecialists' => MenuItem::location('header_mega_specialists')->roots()->active()->ordered()->get(),

                'megaBlog' => BlogPost::published()->showInHeader()->latest('published_at')->take(3)->get(),

                'footerMenu' => MenuItem::location('footer')->roots()->active()->ordered()
                    ->with(['children' => fn ($q) => $q->active()->ordered()])
                    ->get(),

                'footerLegal' => MenuItem::location('footer_legal')->roots()->active()->ordered()->get(),

                'socialLinks' => SocialLink::active()->ordered()->get(),

                'cartCount' => auth()->check()
                    ? CartItem::where('user_id', auth()->id())->count()
                    : 0,
            ]);
        });
    }
}
