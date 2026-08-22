<?php

namespace App\Support;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class GoogleMapsCspPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::SCRIPT, Keyword::UNSAFE_EVAL)
            ->add(Directive::SCRIPT, 'https://maps.googleapis.com')
            ->add(Directive::SCRIPT, 'https://*.googleapis.com')
            ->add(Directive::SCRIPT, 'https://unpkg.com')
            ->add(Directive::IMG, ['https://*.googleapis.com', 'https://*.gstatic.com', '*.google.com', '*.googleusercontent.com', 'data:'])
            ->add(Directive::CONNECT, ['https://*.googleapis.com', '*.google.com', 'https://*.gstatic.com', 'https://unpkg.com'])
            ->add(Directive::FONT, 'https://fonts.gstatic.com');
    }
}
