<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case Ga4 = 'ga4';
    case GoogleAds = 'google_ads';
    case Gtm = 'gtm';
    case MetaPixel = 'meta_pixel';
    case Clarity = 'clarity';
    case Plausible = 'plausible';
    case Custom = 'custom';
}
